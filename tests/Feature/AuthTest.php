<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->userData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+254712345678',
        'password' => 'Password123!@#',
        'password_confirmation' => 'Password123!@#',
        'role' => 'customer',
    ];
});

test('user can register with valid data', function () {
    $response = $this->postJson('/api/v1/register', $this->userData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'user' => ['id', 'name', 'email', 'phone', 'role'],
            'token',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'phone' => '+254712345678',
        'role' => 'customer',
    ]);
});

test('user cannot register with invalid phone number', function () {
    $this->userData['phone'] = '123456789'; // Invalid format

    $response = $this->postJson('/api/v1/register', $this->userData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['phone']);
});

test('user cannot register with weak password', function () {
    $this->userData['password'] = 'weak';
    $this->userData['password_confirmation'] = 'weak';

    $response = $this->postJson('/api/v1/register', $this->userData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => bcrypt('Password123!@#'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'john@example.com',
        'password' => 'Password123!@#',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'user' => ['id', 'name', 'email'],
            'token',
        ]);
});

test('user cannot login with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => bcrypt('Password123!@#'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'john@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('authenticated user can logout', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/logout');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Logged out successfully.']);
});

test('authenticated user can get their profile', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/me');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'phone', 'role'],
        ])
        ->assertJson([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
            ],
        ]);
});

test('unauthenticated user cannot access protected routes', function () {
    $response = $this->getJson('/api/v1/me');

    $response->assertStatus(401);
});

test('login endpoint is rate limited', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => bcrypt('Password123!@#'),
    ]);

    // Attempt login 6 times (limit is 5 per minute)
    for ($i = 0; $i < 6; $i++) {
        $this->postJson('/api/v1/login', [
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    $response = $this->postJson('/api/v1/login', [
        'email' => 'john@example.com',
        'password' => 'Password123!@#',
    ]);

    $response->assertStatus(429); // Too Many Requests
});
