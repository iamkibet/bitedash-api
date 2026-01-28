<?php

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('customer can create order', function () {
    $customer = User::factory()->customer()->create();
    $restaurant = Restaurant::factory()->open()->create();
    $menuItem1 = MenuItem::factory()->available()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 500.00,
    ]);
    $menuItem2 = MenuItem::factory()->available()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 1000.00,
    ]);
    Sanctum::actingAs($customer);

    $orderData = [
        'restaurant_id' => $restaurant->id,
        'items' => [
            ['menu_item_id' => $menuItem1->id, 'quantity' => 2],
            ['menu_item_id' => $menuItem2->id, 'quantity' => 1],
        ],
        'delivery_address' => 'Westlands, Nairobi',
    ];

    $response = $this->postJson('/api/v1/orders', $orderData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'total_amount', 'status', 'payment_status'],
        ]);

    // Verify order was created with correct total (server-side calculation)
    // 2 * 500 + 1 * 1000 = 2000
    $order = Order::latest()->first();
    expect((float) $order->total_amount)->toBe(2000.00);
    expect($order->orderItems)->toHaveCount(2);
});

test('order total is calculated server-side from database prices', function () {
    $customer = User::factory()->customer()->create();
    $restaurant = Restaurant::factory()->open()->create();
    $menuItem = MenuItem::factory()->available()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 1000.00,
    ]);
    Sanctum::actingAs($customer);

    // Try to send wrong total from client
    $orderData = [
        'restaurant_id' => $restaurant->id,
        'items' => [
            ['menu_item_id' => $menuItem->id, 'quantity' => 2],
        ],
        'total_amount' => 500.00, // Client sends wrong amount
    ];

    $response = $this->postJson('/api/v1/orders', $orderData);

    $response->assertStatus(201);
    
    // Server should calculate: 2 * 1000 = 2000, not 500
    $order = Order::latest()->first();
    expect((float) $order->total_amount)->toBe(2000.00); // Server-side calculation wins
});

test('customer cannot create order with unavailable menu item', function () {
    $customer = User::factory()->customer()->create();
    $restaurant = Restaurant::factory()->open()->create();
    $menuItem = MenuItem::factory()->unavailable()->create([
        'restaurant_id' => $restaurant->id,
    ]);
    Sanctum::actingAs($customer);

    $orderData = [
        'restaurant_id' => $restaurant->id,
        'items' => [
            ['menu_item_id' => $menuItem->id, 'quantity' => 1],
        ],
    ];

    $response = $this->postJson('/api/v1/orders', $orderData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

test('restaurant owner can view pending orders for their restaurant', function () {
    $owner = User::factory()->restaurant()->create();
    $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id]);
    $order = Order::factory()->pending()->create(['restaurant_id' => $restaurant->id]);
    Sanctum::actingAs($owner);

    $response = $this->getJson("/api/v1/restaurants/{$restaurant->id}/orders/pending");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'status', 'payment_status'],
            ],
        ]);
});

test('restaurant owner can update order status from pending to preparing', function () {
    $owner = User::factory()->restaurant()->create();
    $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id]);
    $order = Order::factory()->pending()->create(['restaurant_id' => $restaurant->id]);
    Sanctum::actingAs($owner);

    $response = $this->putJson("/api/v1/orders/{$order->id}", [
        'status' => Order::STATUS_PREPARING,
    ]);

    $response->assertStatus(200);
    expect($order->fresh()->status)->toBe(Order::STATUS_PREPARING);
});

test('order cannot skip status transitions', function () {
    $owner = User::factory()->restaurant()->create();
    $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id]);
    $order = Order::factory()->pending()->create(['restaurant_id' => $restaurant->id]);
    Sanctum::actingAs($owner);

    // Try to jump from pending directly to delivered (should fail)
    $response = $this->putJson("/api/v1/orders/{$order->id}", [
        'status' => Order::STATUS_DELIVERED,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

test('rider can view available orders', function () {
    $rider = User::factory()->rider()->create();
    Order::factory()->preparing()->create(['payment_status' => Order::PAYMENT_STATUS_PAID]);
    Sanctum::actingAs($rider);

    $response = $this->getJson('/api/v1/orders/available');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'status', 'payment_status'],
            ],
        ]);
});

test('rider can accept an order', function () {
    $rider = User::factory()->rider()->create();
    $order = Order::factory()->preparing()->create([
        'payment_status' => Order::PAYMENT_STATUS_PAID,
        'rider_id' => null,
    ]);
    Sanctum::actingAs($rider);

    $response = $this->postJson("/api/v1/orders/{$order->id}/accept");

    $response->assertStatus(200);
    expect($order->fresh()->rider_id)->toBe($rider->id);
});

test('rider cannot accept order that is not in preparing status', function () {
    $rider = User::factory()->rider()->create();
    $order = Order::factory()->pending()->create([
        'payment_status' => Order::PAYMENT_STATUS_PAID,
    ]);
    Sanctum::actingAs($rider);

    $response = $this->postJson("/api/v1/orders/{$order->id}/accept");

    // Authorization fails first (403) because order is not in preparing status
    $response->assertStatus(403);
});

test('customer can cancel their pending order', function () {
    $customer = User::factory()->customer()->create();
    $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);
    Sanctum::actingAs($customer);

    $response = $this->postJson("/api/v1/orders/{$order->id}/cancel");

    $response->assertStatus(200);
    expect($order->fresh()->status)->toBe(Order::STATUS_CANCELLED);
});

test('customer cannot cancel order that is not pending', function () {
    $customer = User::factory()->customer()->create();
    $order = Order::factory()->delivered()->create(['customer_id' => $customer->id]);
    Sanctum::actingAs($customer);

    $response = $this->postJson("/api/v1/orders/{$order->id}/cancel");

    // Authorization fails first (403) because order is not pending
    $response->assertStatus(403);
});

test('customer can only view their own orders', function () {
    $customer1 = User::factory()->customer()->create();
    $customer2 = User::factory()->customer()->create();
    $order = Order::factory()->create(['customer_id' => $customer1->id]);
    Sanctum::actingAs($customer2);

    $response = $this->getJson("/api/v1/orders/{$order->id}");

    $response->assertStatus(403);
});
