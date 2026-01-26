<?php

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('authenticated user can view all restaurants', function () {
    $user = User::factory()->customer()->create();
    Sanctum::actingAs($user);

    Restaurant::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/restaurants');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'description', 'location', 'is_open'],
            ],
            'meta',
        ]);
});

test('user can filter restaurants by open status', function () {
    $user = User::factory()->customer()->create();
    Sanctum::actingAs($user);

    Restaurant::factory()->open()->count(2)->create();
    Restaurant::factory()->closed()->count(2)->create();

    $response = $this->getJson('/api/v1/restaurants?is_open=true');

    $response->assertStatus(200);
    expect($response->json('data'))->each->toHaveKey('is_open', true);
});

test('restaurant owner can create restaurant', function () {
    $owner = User::factory()->restaurant()->create();
    Sanctum::actingAs($owner);

    $restaurantData = [
        'name' => 'Test Restaurant',
        'description' => 'A test restaurant',
        'location' => 'Nairobi, Kenya',
        'latitude' => -1.2921,
        'longitude' => 36.8219,
        'is_open' => true,
    ];

    $response = $this->postJson('/api/v1/restaurants', $restaurantData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'name', 'description', 'location'],
        ]);

    $this->assertDatabaseHas('restaurants', [
        'name' => 'Test Restaurant',
        'owner_id' => $owner->id,
    ]);
});

test('customer cannot create restaurant', function () {
    $customer = User::factory()->customer()->create();
    Sanctum::actingAs($customer);

    $restaurantData = [
        'name' => 'Test Restaurant',
        'location' => 'Nairobi, Kenya',
    ];

    $response = $this->postJson('/api/v1/restaurants', $restaurantData);

    $response->assertStatus(403);
});

test('restaurant owner can update their restaurant', function () {
    $owner = User::factory()->restaurant()->create();
    $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id]);
    Sanctum::actingAs($owner);

    $response = $this->putJson("/api/v1/restaurants/{$restaurant->id}", [
        'name' => 'Updated Restaurant Name',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('restaurants', [
        'id' => $restaurant->id,
        'name' => 'Updated Restaurant Name',
    ]);
});

test('restaurant owner cannot update other restaurant', function () {
    $owner1 = User::factory()->restaurant()->create();
    $owner2 = User::factory()->restaurant()->create();
    $restaurant = Restaurant::factory()->create(['owner_id' => $owner1->id]);
    Sanctum::actingAs($owner2);

    $response = $this->putJson("/api/v1/restaurants/{$restaurant->id}", [
        'name' => 'Hacked Name',
    ]);

    $response->assertStatus(403);
});

test('restaurant owner can toggle restaurant status', function () {
    $owner = User::factory()->restaurant()->create();
    $restaurant = Restaurant::factory()->open()->create(['owner_id' => $owner->id]);
    Sanctum::actingAs($owner);

    $response = $this->postJson("/api/v1/restaurants/{$restaurant->id}/toggle-status");

    $response->assertStatus(200);
    expect($restaurant->fresh()->is_open)->toBeFalse();
});

test('admin can delete any restaurant', function () {
    $admin = User::factory()->admin()->create();
    $restaurant = Restaurant::factory()->create();
    Sanctum::actingAs($admin);

    $response = $this->deleteJson("/api/v1/restaurants/{$restaurant->id}");

    $response->assertStatus(200);
    $this->assertSoftDeleted('restaurants', ['id' => $restaurant->id]);
});
