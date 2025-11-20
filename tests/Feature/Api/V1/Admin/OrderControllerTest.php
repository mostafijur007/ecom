<?php

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;

uses()->group('admin', 'orders');

beforeEach(function () {
    $this->artisan('migrate:fresh');
    
    $admin = User::factory()->create(['role' => 'admin']);
    
    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => $admin->email,
        'password' => 'password',
    ]);
    
    $this->token = $loginResponse->json('data.access_token');
});

test('admin can list orders', function () {
    Order::factory()->count(3)->create();

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->getJson('/api/v1/admin/orders');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data'
        ]);
});

test('admin can view single order', function () {
    $order = Order::factory()->create();

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->getJson("/api/v1/admin/orders/{$order->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
            ]
        ]);
});

test('admin can update order status', function () {
    $order = Order::factory()->create(['status' => 'pending']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->patchJson("/api/v1/admin/orders/{$order->id}/status", [
        'status' => 'processing',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => 'processing',
    ]);
});

test('admin can update payment status', function () {
    $order = Order::factory()->create(['payment_status' => 'pending']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->patchJson("/api/v1/admin/orders/{$order->id}/payment", [
        'payment_status' => 'paid',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'payment_status' => 'paid',
    ]);
});

test('admin can cancel order', function () {
    $order = Order::factory()->create(['status' => 'pending']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->postJson("/api/v1/admin/orders/{$order->id}/cancel", [
        'reason' => 'Customer requested cancellation',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => 'cancelled',
    ]);
});

test('admin can get pending orders', function () {
    Order::factory()->create(['status' => 'pending']);
    Order::factory()->create(['status' => 'processing']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->getJson('/api/v1/admin/orders/pending');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data'
        ]);
});

test('admin can get order statistics', function () {
    Order::factory()->count(5)->create(['status' => 'delivered']);
    Order::factory()->count(3)->create(['status' => 'pending']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->getJson('/api/v1/admin/orders/statistics');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data'
        ]);
});

test('non admin cannot access admin orders', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    
    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => $customer->email,
        'password' => 'password',
    ]);
    
    $customerToken = $loginResponse->json('data.access_token');

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $customerToken,
    ])->getJson('/api/v1/admin/orders');

    $response->assertStatus(403);
});

test('cannot update order to invalid status', function () {
    $order = Order::factory()->create(['status' => 'delivered']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->patchJson("/api/v1/admin/orders/{$order->id}/status", [
        'status' => 'invalid_status',
    ]);

    $response->assertStatus(422);
});
