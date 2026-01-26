<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('customer can initiate payment for their order', function () {
    Http::fake([
        'api.paystack.co/charge' => Http::response([
            'status' => true,
            'message' => 'Charge attempted',
            'data' => [
                'reference' => 'TXN_TEST123',
                'status' => 'pending',
                'message' => 'Please check your phone',
            ],
        ], 200),
    ]);

    $customer = User::factory()->customer()->create();
    $order = Order::factory()->pending()->create([
        'customer_id' => $customer->id,
        'payment_status' => Order::PAYMENT_STATUS_UNPAID,
    ]);
    Sanctum::actingAs($customer);

    $response = $this->postJson("/api/v1/orders/{$order->id}/payments/initiate", [
        'phone_number' => '+254712345678',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'transaction_reference', 'status'],
        ]);

    $this->assertDatabaseHas('payments', [
        'order_id' => $order->id,
        'status' => Payment::STATUS_PENDING,
    ]);
});

test('customer cannot initiate payment for already paid order', function () {
    $customer = User::factory()->customer()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'payment_status' => Order::PAYMENT_STATUS_PAID,
    ]);
    Sanctum::actingAs($customer);

    $response = $this->postJson("/api/v1/orders/{$order->id}/payments/initiate", [
        'phone_number' => '+254712345678',
    ]);

    $response->assertStatus(422);
});

test('webhook updates order status when payment succeeds', function () {
    $order = Order::factory()->pending()->create([
        'payment_status' => Order::PAYMENT_STATUS_UNPAID,
    ]);
    $payment = Payment::factory()->pending()->create([
        'order_id' => $order->id,
        'transaction_reference' => 'TXN_TEST123',
    ]);

    $secretKey = config('services.paystack.secret_key', 'test_secret');
    $payloadData = [
        'event' => 'charge.success',
        'data' => [
            'reference' => 'TXN_TEST123',
            'status' => 'success',
            'amount' => $order->total_amount * 100,
        ],
    ];
    $payload = json_encode($payloadData);
    $signature = hash_hmac('sha512', $payload, $secretKey);

    $response = $this->postJson('/api/v1/payments/webhook', $payloadData, [
        'x-paystack-signature' => $signature,
    ]);

    $response->assertStatus(200);

    // Verify order and payment status updated
    expect($payment->fresh()->status)->toBe(Payment::STATUS_SUCCESS);
    expect($order->fresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PAID);
    expect($order->fresh()->status)->toBe(Order::STATUS_PREPARING);
});

test('webhook rejects invalid signature', function () {
    $payloadData = [
        'event' => 'charge.success',
        'data' => ['reference' => 'TXN_TEST123'],
    ];

    $response = $this->postJson('/api/v1/payments/webhook', $payloadData, [
        'x-paystack-signature' => 'invalid_signature',
    ]);

    $response->assertStatus(401);
});

test('webhook handles failed payment', function () {
    $order = Order::factory()->pending()->create([
        'payment_status' => Order::PAYMENT_STATUS_UNPAID,
    ]);
    $payment = Payment::factory()->pending()->create([
        'order_id' => $order->id,
        'transaction_reference' => 'TXN_TEST123',
    ]);

    $secretKey = config('services.paystack.secret_key', 'test_secret');
    $payloadData = [
        'event' => 'charge.failed',
        'data' => [
            'reference' => 'TXN_TEST123',
            'gateway_response' => 'Insufficient funds',
        ],
    ];
    $payload = json_encode($payloadData);
    $signature = hash_hmac('sha512', $payload, $secretKey);

    $response = $this->postJson('/api/v1/payments/webhook', $payloadData, [
        'x-paystack-signature' => $signature,
    ]);

    $response->assertStatus(200);

    expect($payment->fresh()->status)->toBe(Payment::STATUS_FAILED);
    expect($order->fresh()->payment_status)->toBe(Order::PAYMENT_STATUS_FAILED);
});

test('customer can verify payment status', function () {
    $customer = User::factory()->customer()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $payment = Payment::factory()->successful()->create([
        'order_id' => $order->id,
        'transaction_reference' => 'TXN_TEST123',
    ]);
    Sanctum::actingAs($customer);

    $response = $this->getJson("/api/v1/payments/TXN_TEST123/verify");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['id', 'status', 'transaction_reference'],
        ]);
});
