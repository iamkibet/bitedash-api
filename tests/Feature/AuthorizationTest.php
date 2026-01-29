<?php

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Rating;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

// ============================================================================
// PUBLIC ROUTES - Should be accessible to everyone (including unauthenticated)
// ============================================================================

test('unauthenticated user can view all stores', function () {
    Restaurant::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/stores');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'description', 'location'],
            ],
        ]);
});

test('unauthenticated user can view a specific store', function () {
    $restaurant = Restaurant::factory()->create();

    $response = $this->getJson("/api/v1/stores/{$restaurant->id}");

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['id', 'name', 'description']]);
});

test('unauthenticated user can view menu items for a store', function () {
    $restaurant = Restaurant::factory()->create();
    MenuItem::factory()->count(3)->create(['restaurant_id' => $restaurant->id]);

    $response = $this->getJson("/api/v1/stores/{$restaurant->id}/menu-items");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'price', 'is_available'],
            ],
        ]);
});

test('unauthenticated user can view a specific menu item', function () {
    $menuItem = MenuItem::factory()->create();

    $response = $this->getJson("/api/v1/menu-items/{$menuItem->id}");

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['id', 'name', 'price']]);
});

test('unauthenticated user cannot access protected routes', function () {
    $response = $this->getJson('/api/v1/me');
    $response->assertStatus(401);

    $response = $this->getJson('/api/v1/orders');
    $response->assertStatus(401);

    $response = $this->postJson('/api/v1/logout');
    $response->assertStatus(401);
});

// ============================================================================
// AUTHENTICATION ROUTES - Should be accessible to authenticated users
// ============================================================================

test('authenticated user can access /me endpoint', function () {
    $user = User::factory()->customer()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/me');

    $response->assertStatus(200)
        ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role']]);
});

test('authenticated user can logout', function () {
    $user = User::factory()->customer()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/logout');

    $response->assertStatus(200);
});

// ============================================================================
// CUSTOMER-ONLY ROUTES
// ============================================================================

test('customer can create order', function () {
    $customer = User::factory()->customer()->create();
    $restaurant = Restaurant::factory()->open()->create();
    $menuItem = MenuItem::factory()->available()->create(['restaurant_id' => $restaurant->id]);
    Sanctum::actingAs($customer);

    $response = $this->postJson('/api/v1/orders', [
        'restaurant_id' => $restaurant->id,
        'items' => [['menu_item_id' => $menuItem->id, 'quantity' => 1]],
        'delivery_address' => 'Test Address',
    ]);

    $response->assertStatus(201);
});

test('restaurant owner cannot create order', function () {
    $restaurantOwner = User::factory()->restaurant()->create();
    $restaurant = Restaurant::factory()->create(['owner_id' => $restaurantOwner->id]);
    $menuItem = MenuItem::factory()->create(['restaurant_id' => $restaurant->id]);
    Sanctum::actingAs($restaurantOwner);

    $response = $this->postJson('/api/v1/orders', [
        'restaurant_id' => $restaurant->id,
        'items' => [['menu_item_id' => $menuItem->id, 'quantity' => 1]],
        'delivery_address' => 'Test Address',
    ]);

    $response->assertStatus(403);
});

test('rider cannot create order', function () {
    $rider = User::factory()->rider()->create();
    $restaurant = Restaurant::factory()->create();
    $menuItem = MenuItem::factory()->create(['restaurant_id' => $restaurant->id]);
    Sanctum::actingAs($rider);

    $response = $this->postJson('/api/v1/orders', [
        'restaurant_id' => $restaurant->id,
        'items' => [['menu_item_id' => $menuItem->id, 'quantity' => 1]],
        'delivery_address' => 'Test Address',
    ]);

    $response->assertStatus(403);
});

test('customer can initiate payment', function () {
    $customer = User::factory()->customer()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id]);
    Sanctum::actingAs($customer);

    $response = $this->postJson("/api/v1/orders/{$order->id}/payments/initiate", [
        'phone_number' => '+254712345678',
    ]);

    // Should either succeed (201) or fail validation (422), but not 403
    expect($response->status())->not->toBe(403);
});

test('restaurant owner cannot initiate payment', function () {
    $restaurantOwner = User::factory()->restaurant()->create();
    $order = Order::factory()->create();
    Sanctum::actingAs($restaurantOwner);

    $response = $this->postJson("/api/v1/orders/{$order->id}/payments/initiate", [
        'phone_number' => '+254712345678',
    ]);

    $response->assertStatus(403);
});

test('rider cannot initiate payment', function () {
    $rider = User::factory()->rider()->create();
    $order = Order::factory()->create();
    Sanctum::actingAs($rider);

    $response = $this->postJson("/api/v1/orders/{$order->id}/payments/initiate", [
        'phone_number' => '+254712345678',
    ]);

    $response->assertStatus(403);
});

// ============================================================================
// RESTAURANT-ONLY ROUTES
// ============================================================================

test('restaurant owner can create store', function () {
    $restaurantOwner = User::factory()->restaurant()->create();
    Sanctum::actingAs($restaurantOwner);

    $response = $this->postJson('/api/v1/stores', [
        'name' => 'Test Restaurant',
        'description' => 'Test Description',
        'location' => 'Nairobi, Kenya',
        'latitude' => -1.2921,
        'longitude' => 36.8219,
    ]);

    $response->assertStatus(201);
});

test('customer cannot create store', function () {
    $customer = User::factory()->customer()->create();
    Sanctum::actingAs($customer);

    $response = $this->postJson('/api/v1/stores', [
        'name' => 'Test Restaurant',
        'description' => 'Test Description',
        'location' => 'Nairobi, Kenya',
    ]);

    $response->assertStatus(403);
});

test('rider cannot create store', function () {
    $rider = User::factory()->rider()->create();
    Sanctum::actingAs($rider);

    $response = $this->postJson('/api/v1/stores', [
        'name' => 'Test Restaurant',
        'description' => 'Test Description',
        'location' => 'Nairobi, Kenya',
    ]);

    $response->assertStatus(403);
});

test('restaurant owner can access my-store endpoint', function () {
    $restaurantOwner = User::factory()->restaurant()->create();
    Restaurant::factory()->create(['owner_id' => $restaurantOwner->id]);
    Sanctum::actingAs($restaurantOwner);

    $response = $this->getJson('/api/v1/stores/my-store');

    $response->assertStatus(200);
});

test('customer cannot access my-store endpoint', function () {
    $customer = User::factory()->customer()->create();
    Sanctum::actingAs($customer);

    $response = $this->getJson('/api/v1/stores/my-store');

    $response->assertStatus(403);
});

test('rider cannot access my-store endpoint', function () {
    $rider = User::factory()->rider()->create();
    Sanctum::actingAs($rider);

    $response = $this->getJson('/api/v1/stores/my-store');

    $response->assertStatus(403);
});

test('restaurant owner can create menu item', function () {
    $restaurantOwner = User::factory()->restaurant()->create();
    $restaurant = Restaurant::factory()->create(['owner_id' => $restaurantOwner->id]);
    Sanctum::actingAs($restaurantOwner);

    $response = $this->postJson('/api/v1/menu-items', [
        'restaurant_id' => $restaurant->id,
        'name' => 'Test Item',
        'price' => 500.00,
        'description' => 'Test Description',
    ]);

    $response->assertStatus(201);
});

test('customer cannot create menu item', function () {
    $customer = User::factory()->customer()->create();
    $restaurant = Restaurant::factory()->create();
    Sanctum::actingAs($customer);

    $response = $this->postJson('/api/v1/menu-items', [
        'restaurant_id' => $restaurant->id,
        'name' => 'Test Item',
        'price' => 500.00,
    ]);

    $response->assertStatus(403);
});

test('rider cannot create menu item', function () {
    $rider = User::factory()->rider()->create();
    $restaurant = Restaurant::factory()->create();
    Sanctum::actingAs($rider);

    $response = $this->postJson('/api/v1/menu-items', [
        'restaurant_id' => $restaurant->id,
        'name' => 'Test Item',
        'price' => 500.00,
    ]);

    $response->assertStatus(403);
});

test('restaurant owner can view their restaurant orders', function () {
    $restaurantOwner = User::factory()->restaurant()->create();
    $restaurant = Restaurant::factory()->create(['owner_id' => $restaurantOwner->id]);
    Order::factory()->create(['restaurant_id' => $restaurant->id]);
    Sanctum::actingAs($restaurantOwner);

    $response = $this->getJson('/api/v1/orders/my-restaurant');

    // Should return 200 even if no orders (empty array)
    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta']);
});

test('customer cannot view restaurant orders', function () {
    $customer = User::factory()->customer()->create();
    Sanctum::actingAs($customer);

    $response = $this->getJson('/api/v1/orders/my-restaurant');

    $response->assertStatus(403);
});

test('rider cannot view restaurant orders', function () {
    $rider = User::factory()->rider()->create();
    Sanctum::actingAs($rider);

    $response = $this->getJson('/api/v1/orders/my-restaurant');

    $response->assertStatus(403);
});

test('restaurant owner can view pending orders for their restaurant', function () {
    $restaurantOwner = User::factory()->restaurant()->create();
    $restaurant = Restaurant::factory()->create(['owner_id' => $restaurantOwner->id]);
    Sanctum::actingAs($restaurantOwner);

    $response = $this->getJson("/api/v1/stores/{$restaurant->id}/orders/pending");

    $response->assertStatus(200);
});

test('customer cannot view restaurant pending orders', function () {
    $customer = User::factory()->customer()->create();
    $restaurant = Restaurant::factory()->create();
    Sanctum::actingAs($customer);

    $response = $this->getJson("/api/v1/stores/{$restaurant->id}/orders/pending");

    $response->assertStatus(403);
});

test('rider cannot view restaurant pending orders', function () {
    $rider = User::factory()->rider()->create();
    $restaurant = Restaurant::factory()->create();
    Sanctum::actingAs($rider);

    $response = $this->getJson("/api/v1/stores/{$restaurant->id}/orders/pending");

    $response->assertStatus(403);
});

// ============================================================================
// RIDER-ONLY ROUTES
// ============================================================================

test('rider can view available orders', function () {
    $rider = User::factory()->rider()->create();
    Order::factory()->preparing()->create(['payment_status' => Order::PAYMENT_STATUS_PAID]);
    Sanctum::actingAs($rider);

    $response = $this->getJson('/api/v1/orders/available');

    $response->assertStatus(200);
});

test('customer cannot view available orders', function () {
    $customer = User::factory()->customer()->create();
    Sanctum::actingAs($customer);

    $response = $this->getJson('/api/v1/orders/available');

    $response->assertStatus(403);
});

test('restaurant owner cannot view available orders', function () {
    $restaurantOwner = User::factory()->restaurant()->create();
    Sanctum::actingAs($restaurantOwner);

    $response = $this->getJson('/api/v1/orders/available');

    $response->assertStatus(403);
});

test('rider can view their assigned orders', function () {
    $rider = User::factory()->rider()->create();
    Order::factory()->create(['rider_id' => $rider->id]);
    Sanctum::actingAs($rider);

    $response = $this->getJson('/api/v1/orders/my-rider');

    $response->assertStatus(200);
});

test('customer cannot view rider orders', function () {
    $customer = User::factory()->customer()->create();
    Sanctum::actingAs($customer);

    $response = $this->getJson('/api/v1/orders/my-rider');

    $response->assertStatus(403);
});

test('restaurant owner cannot view rider orders', function () {
    $restaurantOwner = User::factory()->restaurant()->create();
    Sanctum::actingAs($restaurantOwner);

    $response = $this->getJson('/api/v1/orders/my-rider');

    $response->assertStatus(403);
});

test('rider can accept order', function () {
    $rider = User::factory()->rider()->create();
    $order = Order::factory()->preparing()->create([
        'payment_status' => Order::PAYMENT_STATUS_PAID,
        'rider_id' => null,
    ]);
    Sanctum::actingAs($rider);

    $response = $this->postJson("/api/v1/orders/{$order->id}/accept");

    $response->assertStatus(200);
});

test('customer cannot accept order', function () {
    $customer = User::factory()->customer()->create();
    $order = Order::factory()->preparing()->create(['payment_status' => Order::PAYMENT_STATUS_PAID]);
    Sanctum::actingAs($customer);

    $response = $this->postJson("/api/v1/orders/{$order->id}/accept");

    $response->assertStatus(403);
});

test('restaurant owner cannot accept order', function () {
    $restaurantOwner = User::factory()->restaurant()->create();
    $order = Order::factory()->preparing()->create(['payment_status' => Order::PAYMENT_STATUS_PAID]);
    Sanctum::actingAs($restaurantOwner);

    $response = $this->postJson("/api/v1/orders/{$order->id}/accept");

    $response->assertStatus(403);
});

// ============================================================================
// ADMIN ROUTES - Admin should have access to everything
// ============================================================================

test('admin can view all users', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->count(3)->create();
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/users');

    $response->assertStatus(200);
});

test('admin can view all restaurants', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Restaurant::factory()->count(3)->create();
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/stores');

    $response->assertStatus(200);
});

test('admin can view all orders', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Order::factory()->count(3)->create();
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/v1/orders');

    $response->assertStatus(200);
});

test('admin can delete any restaurant', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $restaurant = Restaurant::factory()->create();
    Sanctum::actingAs($admin);

    $response = $this->deleteJson("/api/v1/stores/{$restaurant->id}");

    $response->assertStatus(200);
});

test('admin can update any restaurant', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $restaurant = Restaurant::factory()->create();
    Sanctum::actingAs($admin);

    $response = $this->putJson("/api/v1/stores/{$restaurant->id}", [
        'name' => 'Updated Name',
        'location' => $restaurant->location,
    ]);

    $response->assertStatus(200);
});

test('admin can update any menu item', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $menuItem = MenuItem::factory()->create();
    Sanctum::actingAs($admin);

    $response = $this->putJson("/api/v1/menu-items/{$menuItem->id}", [
        'name' => 'Updated Name',
        'price' => $menuItem->price,
    ]);

    $response->assertStatus(200);
});

test('admin can delete any menu item', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $menuItem = MenuItem::factory()->create();
    Sanctum::actingAs($admin);

    $response = $this->deleteJson("/api/v1/menu-items/{$menuItem->id}");

    $response->assertStatus(200);
});

test('admin can view any order', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = Order::factory()->create();
    Sanctum::actingAs($admin);

    $response = $this->getJson("/api/v1/orders/{$order->id}");

    $response->assertStatus(200);
});

test('admin can update any order', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = Order::factory()->pending()->create();
    Sanctum::actingAs($admin);

    $response = $this->putJson("/api/v1/orders/{$order->id}", [
        'status' => Order::STATUS_PREPARING,
    ]);

    $response->assertStatus(200);
});

test('admin can delete any order', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = Order::factory()->create();
    Sanctum::actingAs($admin);

    $response = $this->deleteJson("/api/v1/orders/{$order->id}");

    $response->assertStatus(200);
});

// ============================================================================
// CROSS-ROLE ACCESS RESTRICTIONS
// ============================================================================

test('restaurant owner cannot access rider-only routes', function () {
    $restaurantOwner = User::factory()->restaurant()->create();
    Sanctum::actingAs($restaurantOwner);

    // Rider-only routes
    $response = $this->getJson('/api/v1/orders/available');
    $response->assertStatus(403);

    $response = $this->getJson('/api/v1/orders/my-rider');
    $response->assertStatus(403);
});

test('rider cannot access restaurant-only routes', function () {
    $rider = User::factory()->rider()->create();
    Sanctum::actingAs($rider);

    // Restaurant-only routes
    $response = $this->postJson('/api/v1/stores', [
        'name' => 'Test',
        'location' => 'Test',
    ]);
    $response->assertStatus(403);

    $response = $this->getJson('/api/v1/orders/my-restaurant');
    $response->assertStatus(403);

    $response = $this->postJson('/api/v1/menu-items', [
        'restaurant_id' => 1,
        'name' => 'Test',
        'price' => 100,
    ]);
    $response->assertStatus(403);
});

test('customer cannot access restaurant management routes', function () {
    $customer = User::factory()->customer()->create();
    $restaurant = Restaurant::factory()->create();
    Sanctum::actingAs($customer);

    // Restaurant management routes
    $response = $this->postJson('/api/v1/stores', [
        'name' => 'Test',
        'location' => 'Test',
    ]);
    $response->assertStatus(403);

    $response = $this->putJson("/api/v1/stores/{$restaurant->id}", ['name' => 'Updated']);
    $response->assertStatus(403);

    $response = $this->postJson("/api/v1/stores/{$restaurant->id}/toggle-status");
    $response->assertStatus(403);
});

test('customer cannot access rider routes', function () {
    $customer = User::factory()->customer()->create();
    Sanctum::actingAs($customer);

    // Rider routes
    $response = $this->getJson('/api/v1/orders/available');
    $response->assertStatus(403);

    $response = $this->getJson('/api/v1/orders/my-rider');
    $response->assertStatus(403);
});

test('restaurant owner cannot access customer-only routes', function () {
    $restaurantOwner = User::factory()->restaurant()->create();
    $order = Order::factory()->create();
    Sanctum::actingAs($restaurantOwner);

    // Customer-only routes
    $response = $this->postJson('/api/v1/orders', [
        'restaurant_id' => 1,
        'items' => [],
    ]);
    $response->assertStatus(403);

    $response = $this->postJson("/api/v1/orders/{$order->id}/payments/initiate", [
        'phone_number' => '+254712345678',
    ]);
    $response->assertStatus(403);
});

// ============================================================================
// RESOURCE OWNERSHIP CHECKS
// ============================================================================

test('restaurant owner can only update their own restaurant', function () {
    $owner1 = User::factory()->restaurant()->create();
    $owner2 = User::factory()->restaurant()->create();
    $restaurant1 = Restaurant::factory()->create(['owner_id' => $owner1->id]);
    $restaurant2 = Restaurant::factory()->create(['owner_id' => $owner2->id]);
    Sanctum::actingAs($owner1);

    // Can update own restaurant
    $response = $this->putJson("/api/v1/stores/{$restaurant1->id}", [
        'name' => 'Updated',
        'location' => $restaurant1->location,
    ]);
    $response->assertStatus(200);

    // Cannot update other restaurant
    $response = $this->putJson("/api/v1/stores/{$restaurant2->id}", [
        'name' => 'Updated',
        'location' => $restaurant2->location,
    ]);
    $response->assertStatus(403);
});

test('restaurant owner can only update their own menu items', function () {
    $owner1 = User::factory()->restaurant()->create();
    $owner2 = User::factory()->restaurant()->create();
    $restaurant1 = Restaurant::factory()->create(['owner_id' => $owner1->id]);
    $restaurant2 = Restaurant::factory()->create(['owner_id' => $owner2->id]);
    $menuItem1 = MenuItem::factory()->create(['restaurant_id' => $restaurant1->id]);
    $menuItem2 = MenuItem::factory()->create(['restaurant_id' => $restaurant2->id]);
    Sanctum::actingAs($owner1);

    // Can update own menu item
    $response = $this->putJson("/api/v1/menu-items/{$menuItem1->id}", [
        'name' => 'Updated',
        'price' => $menuItem1->price,
    ]);
    $response->assertStatus(200);

    // Cannot update other restaurant's menu item
    $response = $this->putJson("/api/v1/menu-items/{$menuItem2->id}", [
        'name' => 'Updated',
        'price' => $menuItem2->price,
    ]);
    $response->assertStatus(403);
});

test('customer can only view their own orders', function () {
    $customer1 = User::factory()->customer()->create();
    $customer2 = User::factory()->customer()->create();
    $order1 = Order::factory()->create(['customer_id' => $customer1->id]);
    $order2 = Order::factory()->create(['customer_id' => $customer2->id]);
    Sanctum::actingAs($customer1);

    // Can view own order
    $response = $this->getJson("/api/v1/orders/{$order1->id}");
    $response->assertStatus(200);

    // Cannot view other customer's order
    $response = $this->getJson("/api/v1/orders/{$order2->id}");
    $response->assertStatus(403);
});

test('restaurant owner can only view orders for their restaurant', function () {
    $owner1 = User::factory()->restaurant()->create();
    $owner2 = User::factory()->restaurant()->create();
    $restaurant1 = Restaurant::factory()->create(['owner_id' => $owner1->id]);
    $restaurant2 = Restaurant::factory()->create(['owner_id' => $owner2->id]);
    $order1 = Order::factory()->create(['restaurant_id' => $restaurant1->id]);
    $order2 = Order::factory()->create(['restaurant_id' => $restaurant2->id]);
    Sanctum::actingAs($owner1);

    // Can view order for own restaurant
    $response = $this->getJson("/api/v1/orders/{$order1->id}");
    $response->assertStatus(200);

    // Cannot view order for other restaurant
    $response = $this->getJson("/api/v1/orders/{$order2->id}");
    $response->assertStatus(403);
});

test('rider can only view orders assigned to them', function () {
    $rider1 = User::factory()->rider()->create();
    $rider2 = User::factory()->rider()->create();
    $order1 = Order::factory()->create(['rider_id' => $rider1->id]);
    $order2 = Order::factory()->create(['rider_id' => $rider2->id]);
    Sanctum::actingAs($rider1);

    // Can view own assigned order
    $response = $this->getJson("/api/v1/orders/{$order1->id}");
    $response->assertStatus(200);

    // Cannot view order assigned to other rider
    $response = $this->getJson("/api/v1/orders/{$order2->id}");
    $response->assertStatus(403);
});

// ============================================================================
// ADMIN OVERRIDE - Admin can access everything
// ============================================================================

test('admin can access customer-only routes', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $restaurant = Restaurant::factory()->create();
    $menuItem = MenuItem::factory()->create(['restaurant_id' => $restaurant->id]);
    Sanctum::actingAs($admin);

    // Admin can create orders (normally customer-only) - middleware and policy allow admin
    $response = $this->postJson('/api/v1/orders', [
        'restaurant_id' => $restaurant->id,
        'items' => [['menu_item_id' => $menuItem->id, 'quantity' => 1]],
        'delivery_address' => 'Test',
    ]);
    // Should succeed or fail validation, but not authorization (403)
    expect($response->status())->not->toBe(403);
});

test('admin can access restaurant-only routes', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    // Admin can create restaurants - middleware and policy allow admin
    $response = $this->postJson('/api/v1/stores', [
        'name' => 'Admin Restaurant',
        'location' => 'Nairobi',
    ]);
    // Should succeed or fail validation, but not authorization (403)
    expect($response->status())->not->toBe(403);
});

test('admin can access rider-only routes', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Order::factory()->preparing()->create(['payment_status' => Order::PAYMENT_STATUS_PAID]);
    Sanctum::actingAs($admin);

    // Admin can view available orders (normally rider-only) - controller allows admin
    $response = $this->getJson('/api/v1/orders/available');
    $response->assertStatus(200);
});

test('admin can view any user resource', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->customer()->create();
    $restaurantOwner = User::factory()->restaurant()->create();
    $rider = User::factory()->rider()->create();
    Sanctum::actingAs($admin);

    // Admin can view all users
    $response = $this->getJson('/api/v1/users');
    $response->assertStatus(200);

    // Admin can view riders
    $response = $this->getJson('/api/v1/riders');
    $response->assertStatus(200);
});

test('customer cannot access users or riders list', function () {
    $customer = User::factory()->customer()->create();
    Sanctum::actingAs($customer);

    $response = $this->getJson('/api/v1/users');
    $response->assertStatus(403);

    $response = $this->getJson('/api/v1/riders');
    $response->assertStatus(403);
});

test('rider cannot access users or riders list', function () {
    $rider = User::factory()->rider()->create();
    Sanctum::actingAs($rider);

    $response = $this->getJson('/api/v1/users');
    $response->assertStatus(403);

    $response = $this->getJson('/api/v1/riders');
    $response->assertStatus(403);
});

// ============================================================================
// FAVOURITES ROUTES (customer only)
// ============================================================================

test('unauthenticated user cannot access favourites', function () {
    $response = $this->getJson('/api/v1/favourites');
    $response->assertStatus(401);

    $response = $this->postJson('/api/v1/favourites', ['menu_item_id' => 1]);
    $response->assertStatus(401);

    $response = $this->deleteJson('/api/v1/favourites/1');
    $response->assertStatus(401);
});

test('restaurant owner cannot access favourites', function () {
    $restaurantOwner = User::factory()->restaurant()->create();
    $menuItem = MenuItem::factory()->create();
    Sanctum::actingAs($restaurantOwner);

    $response = $this->getJson('/api/v1/favourites');
    $response->assertStatus(403);

    $response = $this->postJson('/api/v1/favourites', ['menu_item_id' => $menuItem->id]);
    $response->assertStatus(403);

    $response = $this->deleteJson("/api/v1/favourites/{$menuItem->id}");
    $response->assertStatus(403);
});

test('rider cannot access favourites', function () {
    $rider = User::factory()->rider()->create();
    $menuItem = MenuItem::factory()->create();
    Sanctum::actingAs($rider);

    $response = $this->getJson('/api/v1/favourites');
    $response->assertStatus(403);

    $response = $this->postJson('/api/v1/favourites', ['menu_item_id' => $menuItem->id]);
    $response->assertStatus(403);

    $response = $this->deleteJson("/api/v1/favourites/{$menuItem->id}");
    $response->assertStatus(403);
});

// ============================================================================
// RATINGS ROUTES (customer only, ownership on update/delete)
// ============================================================================

test('unauthenticated user cannot create or modify ratings', function () {
    $menuItem = MenuItem::factory()->create();

    $response = $this->postJson('/api/v1/ratings', [
        'menu_item_id' => $menuItem->id,
        'rating' => 5,
    ]);
    $response->assertStatus(401);

    $user = User::factory()->create();
    $menuItemForRating = MenuItem::factory()->create();
    $rating = Rating::create(['user_id' => $user->id, 'menu_item_id' => $menuItemForRating->id, 'rating' => 5]);
    $response = $this->putJson("/api/v1/ratings/{$rating->id}", ['rating' => 4]);
    $response->assertStatus(401);

    $response = $this->deleteJson("/api/v1/ratings/{$rating->id}");
    $response->assertStatus(401);
});

test('restaurant owner cannot create or modify ratings', function () {
    $restaurantOwner = User::factory()->restaurant()->create();
    $menuItem = MenuItem::factory()->create(['restaurant_id' => Restaurant::factory()->create()->id]);
    Sanctum::actingAs($restaurantOwner);

    $response = $this->postJson('/api/v1/ratings', [
        'menu_item_id' => $menuItem->id,
        'rating' => 5,
    ]);
    $response->assertStatus(403);

    $otherUser = User::factory()->create();
    $menuItemForRating = MenuItem::factory()->create();
    $rating = Rating::create(['user_id' => $otherUser->id, 'menu_item_id' => $menuItemForRating->id, 'rating' => 5]);
    $response = $this->putJson("/api/v1/ratings/{$rating->id}", ['rating' => 4]);
    $response->assertStatus(403);

    $response = $this->deleteJson("/api/v1/ratings/{$rating->id}");
    $response->assertStatus(403);
});

test('rider cannot create or modify ratings', function () {
    $rider = User::factory()->rider()->create();
    $menuItem = MenuItem::factory()->create();
    Sanctum::actingAs($rider);

    $response = $this->postJson('/api/v1/ratings', [
        'menu_item_id' => $menuItem->id,
        'rating' => 5,
    ]);
    $response->assertStatus(403);

    $otherUser = User::factory()->create();
    $menuItemForRating = MenuItem::factory()->create();
    $rating = Rating::create(['user_id' => $otherUser->id, 'menu_item_id' => $menuItemForRating->id, 'rating' => 5]);
    $response = $this->putJson("/api/v1/ratings/{$rating->id}", ['rating' => 4]);
    $response->assertStatus(403);

    $response = $this->deleteJson("/api/v1/ratings/{$rating->id}");
    $response->assertStatus(403);
});

test('customer can only update their own rating', function () {
    $customer1 = User::factory()->customer()->create();
    $customer2 = User::factory()->customer()->create();
    $menuItem = MenuItem::factory()->create();
    $ratingOwn = Rating::create([
        'user_id' => $customer1->id,
        'menu_item_id' => $menuItem->id,
        'rating' => 5,
    ]);
    $ratingOther = Rating::create([
        'user_id' => $customer2->id,
        'menu_item_id' => $menuItem->id,
        'rating' => 4,
    ]);
    Sanctum::actingAs($customer1);

    $response = $this->putJson("/api/v1/ratings/{$ratingOwn->id}", ['rating' => 3]);
    $response->assertStatus(200);

    $response = $this->putJson("/api/v1/ratings/{$ratingOther->id}", ['rating' => 1]);
    $response->assertStatus(403);
});

test('customer can only delete their own rating', function () {
    $customer1 = User::factory()->customer()->create();
    $customer2 = User::factory()->customer()->create();
    $menuItem = MenuItem::factory()->create();
    Rating::create([
        'user_id' => $customer1->id,
        'menu_item_id' => $menuItem->id,
        'rating' => 5,
    ]);
    $ratingOther = Rating::create([
        'user_id' => $customer2->id,
        'menu_item_id' => $menuItem->id,
        'rating' => 4,
    ]);
    Sanctum::actingAs($customer1);

    $response = $this->deleteJson("/api/v1/ratings/{$ratingOther->id}");
    $response->assertStatus(403);
});
