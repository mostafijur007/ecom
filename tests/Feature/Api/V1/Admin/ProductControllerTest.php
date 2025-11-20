<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

uses()->group('admin', 'products');

beforeEach(function () {
    $this->artisan('migrate:fresh');
    
    $admin = User::factory()->create(['role' => 'admin']);
    
    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => $admin->email,
        'password' => 'password',
    ]);
    
    $this->token = $loginResponse->json('data.access_token');
});

test('admin can list products', function () {
    Product::factory()->count(5)->create();

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->getJson('/api/v1/admin/products');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'data' => [], // Paginated response
                'current_page',
                'per_page'
            ]
        ]);
});

test('admin can create product', function () {
    $category = Category::factory()->create();
    $vendor = User::factory()->create(['role' => 'vendor']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->postJson('/api/v1/admin/products', [
        'name' => 'Test Product',
        'description' => 'Test Description',
        'price' => 99.99,
        'category_id' => $category->id,
        'vendor_id' => $vendor->id,
        'sku' => 'TEST-SKU-001',
        'stock_quantity' => 100,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['id', 'name', 'price', 'sku']
        ]);

    expect(Product::where('name', 'Test Product')->where('sku', 'TEST-SKU-001')->exists())->toBeTrue();
});

test('admin can view single product', function () {
    $product = Product::factory()->create();

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->getJson("/api/v1/admin/products/{$product->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [ // Product data is directly in data, not in data.product
                'id' => $product->id,
                'name' => $product->name,
            ]
        ]);
});

test('admin can update product', function () {
    $product = Product::factory()->create();

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->putJson("/api/v1/admin/products/{$product->id}", [
        'name' => 'Updated Product Name',
        'price' => 149.99,
    ]);

    $response->assertStatus(200);

    expect(Product::where('id', $product->id)->where('name', 'Updated Product Name')->exists())->toBeTrue();
});

test('admin can delete product', function () {
    $product = Product::factory()->create();

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->deleteJson("/api/v1/admin/products/{$product->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('products', [
        'id' => $product->id,
    ]);
});

test('admin can search products', function () {
    Product::factory()->create(['name' => 'iPhone 15']);
    Product::factory()->create(['name' => 'Samsung Galaxy']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->getJson('/api/v1/admin/products/search?q=iPhone');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'data' => [],
                'current_page'
            ]
        ]);
    
    // Check that iPhone product is in results
    $data = $response->json('data.data');
    expect(collect($data)->pluck('name')->contains('iPhone 15'))->toBeTrue();
});

test('non admin cannot create product', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    
    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => $customer->email,
        'password' => 'password',
    ]);
    
    $token = $loginResponse->json('data.access_token');

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->postJson('/api/v1/admin/products', [
        'name' => 'Test Product',
        'price' => 99.99,
    ]);

    $response->assertStatus(403);
});

test('product creation requires valid data', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->postJson('/api/v1/admin/products', [
        'name' => '', // Invalid: empty name
        'price' => -10, // Invalid: negative price
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'price']);
});
