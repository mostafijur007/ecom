<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Category;

uses()->group('vendor', 'products');

beforeEach(function () {
    $this->artisan('migrate:fresh');
    
    $this->vendor = User::factory()->create(['role' => 'vendor']);
    
    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => $this->vendor->email,
        'password' => 'password',
    ]);
    
    $this->token = $loginResponse->json('data.access_token');
});

test('vendor can list own products', function () {
    Product::factory()->count(3)->create(['vendor_id' => $this->vendor->id]);
    Product::factory()->count(2)->create(); // Other vendor's products

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->getJson('/api/v1/vendor/products');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data'
        ]);
});

test('vendor can create product', function () {
    $category = Category::factory()->create();

    $productData = [
        'name' => 'Test Product',
        'description' => 'Test description',
        'sku' => 'TEST-001',
        'price' => 99.99,
        'category_id' => $category->id,
        'stock_quantity' => 100,
        'is_active' => true,
    ];

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->postJson('/api/v1/vendor/products', $productData);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Test Product',
                'vendor_id' => $this->vendor->id,
            ]
        ]);

    $this->assertDatabaseHas('products', [
        'name' => 'Test Product',
        'vendor_id' => $this->vendor->id,
    ]);
});

test('vendor can view own product', function () {
    $product = Product::factory()->create(['vendor_id' => $this->vendor->id]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->getJson("/api/v1/vendor/products/{$product->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
            ]
        ]);
});

test('vendor cannot view other vendors product', function () {
    $otherVendor = User::factory()->create(['role' => 'vendor']);
    $product = Product::factory()->create(['vendor_id' => $otherVendor->id]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->getJson("/api/v1/vendor/products/{$product->id}");

    $response->assertStatus(403);
});

test('vendor can update own product', function () {
    $product = Product::factory()->create([
        'vendor_id' => $this->vendor->id,
        'name' => 'Old Name',
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->putJson("/api/v1/vendor/products/{$product->id}", [
        'name' => 'New Name',
        'description' => 'Updated description',
        'price' => 149.99,
        'stock_quantity' => 50,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'New Name',
    ]);
});

test('vendor cannot update other vendors product', function () {
    $otherVendor = User::factory()->create(['role' => 'vendor']);
    $product = Product::factory()->create(['vendor_id' => $otherVendor->id]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->putJson("/api/v1/vendor/products/{$product->id}", [
        'name' => 'Hacked Name',
    ]);

    $response->assertStatus(403);
});

test('vendor can delete own product', function () {
    $product = Product::factory()->create(['vendor_id' => $this->vendor->id]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->deleteJson("/api/v1/vendor/products/{$product->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('products', [
        'id' => $product->id,
    ]);
});

test('vendor cannot delete other vendors product', function () {
    $otherVendor = User::factory()->create(['role' => 'vendor']);
    $product = Product::factory()->create(['vendor_id' => $otherVendor->id]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->deleteJson("/api/v1/vendor/products/{$product->id}");

    $response->assertStatus(403);
});

test('product creation requires valid data', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->postJson('/api/v1/vendor/products', [
        'name' => '',
        'price' => -10,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'price']);
});

test('vendor can search own products', function () {
    Product::factory()->create([
        'vendor_id' => $this->vendor->id,
        'name' => 'iPhone 15'
    ]);
    Product::factory()->create([
        'vendor_id' => $this->vendor->id,
        'name' => 'Samsung Galaxy'
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->getJson('/api/v1/vendor/products/search?q=iPhone');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data'
        ]);
});
