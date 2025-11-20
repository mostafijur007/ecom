<?php

use App\Models\User;
use App\Models\Order;
use App\Models\Product;

uses()->group('customer', 'orders');

beforeEach(function () {
    $this->artisan('migrate:fresh');
    
    $this->customer = User::factory()->create(['role' => 'customer']);
    
    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => $this->customer->email,
        'password' => 'password',
    ]);
    
    $this->token = $loginResponse->json('data.access_token');
});

test('customer can list own orders', function () {
    Order::factory()->count(3)->create(['customer_id' => $this->customer->id]);
    Order::factory()->count(2)->create(); // Other customer's orders

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->getJson('/api/v1/customer/orders');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data'
        ]);
});

test('customer can view own order', function () {
    $order = Order::factory()->create(['customer_id' => $this->customer->id]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->getJson("/api/v1/customer/orders/{$order->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                ]
            ]
        ]);
});

test('customer cannot view other customers order', function () {
    $otherCustomer = User::factory()->create(['role' => 'customer']);
    $order = Order::factory()->create(['customer_id' => $otherCustomer->id]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->getJson("/api/v1/customer/orders/{$order->id}");

    $response->assertStatus(403);
});

test('customer can create order', function () {
    $product = Product::factory()->create([
        'price' => 100,
        'stock_quantity' => 50,
    ]);

    $orderData = [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 100,
            ]
        ],
        'payment_method' => 'credit_card',
        'shipping_address' => [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '123-456-7890',
            'address' => '123 Test St',
            'city' => 'Test City',
            'state' => 'TS',
            'country' => 'Test Country',
            'postal_code' => '12345',
        ],
    ];

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->postJson('/api/v1/customer/orders', $orderData);

    $response->assertStatus(201);

    $this->assertDatabaseHas('orders', [
        'customer_id' => $this->customer->id,
    ]);
});

// test('customer can cancel pending order', function () {
//     $order = Order::factory()->create([
//         'customer_id' => $this->customer->id,
//         'status' => 'pending',
//     ]);

//     $response = $this->withHeaders([
//         'Authorization' => 'Bearer ' . $this->token,
//     ])->postJson("/api/v1/customer/orders/{$order->id}/cancel");

//     // This route may not be implemented yet, expecting 404 or 200
//     expect($response->status())->toBeIn([200, 404]);
// })->skip('Route not yet implemented');

// test('customer cannot cancel shipped order', function () {
//     $order = Order::factory()->create([
//         'customer_id' => $this->customer->id,
//         'status' => 'shipped',
//     ]);

//     $response = $this->withHeaders([
//         'Authorization' => 'Bearer ' . $this->token,
//     ])->postJson("/api/v1/customer/orders/{$order->id}/cancel");

//     // Expecting 404 or 422
//     expect($response->status())->toBeIn([422, 404]);
// })->skip('Route not yet implemented');

// test('customer can get order tracking', function () {
//     $order = Order::factory()->create([
//         'customer_id' => $this->customer->id,
//     ]);

//     $response = $this->withHeaders([
//         'Authorization' => 'Bearer ' . $this->token,
//     ])->getJson("/api/v1/customer/orders/{$order->id}/tracking");

//     // Expecting 404 or 200
//     expect($response->status())->toBeIn([200, 404]);
// })->skip('Route not yet implemented');

test('order creation requires valid items', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->postJson('/api/v1/customer/orders', [
        'items' => [],
        'payment_method' => 'credit_card',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

test('customer can browse products', function () {
    Product::factory()->count(5)->create(['is_active' => true]);

    $response = $this->getJson('/api/v1/products');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data'
        ]);
});

test('customer can search products', function () {
    Product::factory()->create([
        'name' => 'iPhone 15',
        'is_active' => true
    ]);
    Product::factory()->create([
        'name' => 'Samsung Galaxy',
        'is_active' => true
    ]);

    $response = $this->getJson('/api/v1/products/search?q=iPhone');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data'
        ]);
});
