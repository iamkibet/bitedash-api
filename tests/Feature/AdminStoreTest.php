<?php

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('admin can list all stores with pagination', function () {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    Restaurant::factory()->count(5)->create();

    $response = $this->getJson('/api/v1/admin/stores?page=1&per_page=3');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'description', 'location', 'is_open', 'owner'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    expect($response->json('data'))->toHaveCount(3);
    expect($response->json('meta.per_page'))->toBe(3);
});

test('admin can update store is_open', function () {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $restaurant = Restaurant::factory()->open()->create();

    $response = $this->putJson("/api/v1/admin/stores/{$restaurant->id}", [
        'is_open' => false,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.is_open', false)
        ->assertJsonPath('message', 'Store updated successfully.');

    $restaurant->refresh();
    expect($restaurant->is_open)->toBeFalse();
});

test('admin can update store via PATCH', function () {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $restaurant = Restaurant::factory()->closed()->create();

    $response = $this->patchJson("/api/v1/admin/stores/{$restaurant->id}", [
        'is_open' => true,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.is_open', true);

    $restaurant->refresh();
    expect($restaurant->is_open)->toBeTrue();
});

test('non-admin cannot list admin stores', function () {
    $customer = User::factory()->customer()->create();
    Sanctum::actingAs($customer);

    $response = $this->getJson('/api/v1/admin/stores');

    $response->assertStatus(403);
});

test('non-admin cannot update store via admin endpoint', function () {
    $customer = User::factory()->customer()->create();
    Sanctum::actingAs($customer);

    $restaurant = Restaurant::factory()->create();

    $response = $this->putJson("/api/v1/admin/stores/{$restaurant->id}", [
        'is_open' => false,
    ]);

    $response->assertStatus(403);
});

test('unauthenticated user cannot access admin stores', function () {
    $response = $this->getJson('/api/v1/admin/stores');
    $response->assertStatus(401);

    $restaurant = Restaurant::factory()->create();
    $response = $this->putJson("/api/v1/admin/stores/{$restaurant->id}", ['is_open' => false]);
    $response->assertStatus(401);
});
