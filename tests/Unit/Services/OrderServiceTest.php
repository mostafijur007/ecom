<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Services\OrderService;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $orderService;
    protected $orderRepository;
    protected $productRepository;
    protected $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orderRepository = app(OrderRepository::class);
        $this->productRepository = app(ProductRepository::class);
        $this->inventoryService = app(InventoryService::class);
        
        $this->orderService = new OrderService(
            $this->orderRepository,
            $this->productRepository,
            $this->inventoryService
        );
    }

    /** @test */
    public function it_can_create_order_with_valid_data()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create([
            'price' => 100,
            'stock_quantity' => 50,
        ]);

        $orderData = [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 100,
                ]
            ],
            'payment_method' => 'credit_card',
            'shipping_address' => '123 Test St',
            'shipping_city' => 'Test City',
            'shipping_state' => 'TS',
            'shipping_country' => 'Test Country',
            'shipping_postal_code' => '12345',
        ];

        $order = $this->orderService->create($orderData);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($customer->id, $order->customer_id);
        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
        ]);
    }

    /** @test */
    public function it_calculates_order_totals_correctly()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product1 = Product::factory()->create(['price' => 100, 'stock_quantity' => 50]);
        $product2 = Product::factory()->create(['price' => 50, 'stock_quantity' => 50]);

        $orderData = [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_id' => $product1->id,
                    'quantity' => 2,
                    'unit_price' => 100,
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 3,
                    'unit_price' => 50,
                ]
            ],
            'payment_method' => 'credit_card',
            'shipping_address' => '123 Test St',
            'shipping_city' => 'Test City',
            'shipping_state' => 'TS',
            'shipping_country' => 'Test Country',
            'shipping_postal_code' => '12345',
            'tax' => 35,
            'discount' => 10,
            'shipping_cost' => 15,
        ];

        $order = $this->orderService->create($orderData);

        // Subtotal: (2 * 100) + (3 * 50) = 350
        // Total: 350 + 35 (tax) - 10 (discount) + 15 (shipping) = 390
        $this->assertEquals(390, $order->total);
    }

    /** @test */
    public function it_creates_order_items_correctly()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create([
            'price' => 100,
            'stock_quantity' => 50,
        ]);

        $orderData = [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 100,
                ]
            ],
            'payment_method' => 'credit_card',
            'shipping_address' => '123 Test St',
            'shipping_city' => 'Test City',
            'shipping_state' => 'TS',
            'shipping_country' => 'Test Country',
            'shipping_postal_code' => '12345',
        ];

        $order = $this->orderService->create($orderData);

        $this->assertCount(1, $order->items);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    /** @test */
    public function it_generates_unique_order_number()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create([
            'price' => 100,
            'stock_quantity' => 50,
        ]);

        $orderData = [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                ]
            ],
            'payment_method' => 'credit_card',
            'shipping_address' => '123 Test St',
            'shipping_city' => 'Test City',
            'shipping_state' => 'TS',
            'shipping_country' => 'Test Country',
            'shipping_postal_code' => '12345',
        ];

        $order1 = $this->orderService->create($orderData);
        $order2 = $this->orderService->create($orderData);

        $this->assertNotNull($order1->order_number);
        $this->assertNotNull($order2->order_number);
        $this->assertNotEquals($order1->order_number, $order2->order_number);
    }

    /** @test */
    public function it_can_update_order_status()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $updatedOrder = $this->orderService->updateStatus($order->id, 'processing');

        $this->assertEquals('processing', $updatedOrder->status);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    /** @test */
    public function it_can_cancel_order()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $cancelledOrder = $this->orderService->cancel($order->id, 'Customer request');

        $this->assertEquals('cancelled', $cancelledOrder->status);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }
}
