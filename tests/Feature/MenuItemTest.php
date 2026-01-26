<?php

use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('authenticated user can view menu items for a restaurant', function () {
    $user = User::factory()->customer()->create();
    $restaurant = Restaurant::factory()->create();
    MenuItem::factory()->count(3)->create(['restaurant_id' => $restaurant->id]);
    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/restaurants/{$restaurant->id}/menu-items");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'description', 'price', 'is_available'],
            ],
        ]);
});

test('restaurant owner can create menu item', function () {
    $owner = User::factory()->restaurant()->create();
    $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id]);
    Sanctum::actingAs($owner);

    $menuItemData = [
        'restaurant_id' => $restaurant->id,
        'name' => 'Nyama Choma',
        'description' => 'Grilled meat',
        'price' => 1500.00,
        'is_available' => true,
    ];

    $response = $this->postJson('/api/v1/menu-items', $menuItemData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'name', 'price'],
        ]);

    $this->assertDatabaseHas('menu_items', [
        'name' => 'Nyama Choma',
        'restaurant_id' => $restaurant->id,
    ]);
});

test('restaurant owner cannot create menu item for other restaurant', function () {
    $owner1 = User::factory()->restaurant()->create();
    $owner2 = User::factory()->restaurant()->create();
    $restaurant1 = Restaurant::factory()->create(['owner_id' => $owner1->id]);
    $restaurant2 = Restaurant::factory()->create(['owner_id' => $owner2->id]);
    Sanctum::actingAs($owner1);

    $menuItemData = [
        'restaurant_id' => $restaurant2->id, // Trying to add to owner2's restaurant
        'name' => 'Hacked Item',
        'price' => 1000.00,
    ];

    $response = $this->postJson('/api/v1/menu-items', $menuItemData);

    $response->assertStatus(403);
});

test('restaurant owner can update their menu items', function () {
    $owner = User::factory()->restaurant()->create();
    $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id]);
    $menuItem = MenuItem::factory()->create(['restaurant_id' => $restaurant->id]);
    Sanctum::actingAs($owner);

    $response = $this->putJson("/api/v1/menu-items/{$menuItem->id}", [
        'price' => 2000.00,
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('menu_items', [
        'id' => $menuItem->id,
        'price' => 2000.00,
    ]);
});

test('restaurant owner can toggle menu item availability', function () {
    $owner = User::factory()->restaurant()->create();
    $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id]);
    $menuItem = MenuItem::factory()->available()->create(['restaurant_id' => $restaurant->id]);
    Sanctum::actingAs($owner);

    $response = $this->postJson("/api/v1/menu-items/{$menuItem->id}/toggle-availability");

    $response->assertStatus(200);
    expect($menuItem->fresh()->is_available)->toBeFalse();
});

test('customer cannot create menu items', function () {
    $customer = User::factory()->customer()->create();
    $restaurant = Restaurant::factory()->create();
    Sanctum::actingAs($customer);

    $menuItemData = [
        'restaurant_id' => $restaurant->id,
        'name' => 'Test Item',
        'price' => 1000.00,
    ];

    $response = $this->postJson('/api/v1/menu-items', $menuItemData);

    $response->assertStatus(403);
});
