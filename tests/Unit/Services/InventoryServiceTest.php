<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Models\Inventory;
use App\Services\InventoryService;
use App\Repositories\InventoryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $inventoryService;
    protected $inventoryRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->inventoryRepository = app(InventoryRepository::class);
        $this->inventoryService = new InventoryService($this->inventoryRepository);
    }

    /** @test */
    public function it_can_get_product_stock_quantity()
    {
        $product = Product::factory()->create(['stock_quantity' => 100]);

        $stockQuantity = $this->inventoryService->getStockQuantity($product->id);

        $this->assertEquals(100, $stockQuantity);
    }

    /** @test */
    public function it_can_check_if_product_is_in_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $this->assertTrue($this->inventoryService->isInStock($product->id, 5));
        $this->assertTrue($this->inventoryService->isInStock($product->id, 10));
        $this->assertFalse($this->inventoryService->isInStock($product->id, 15));
    }

    /** @test */
    public function it_can_deduct_stock_quantity()
    {
        $product = Product::factory()->create(['stock_quantity' => 100]);

        $result = $this->inventoryService->deductStock($product->id, 25);

        $this->assertTrue($result);
        $product->refresh();
        $this->assertEquals(75, $product->stock_quantity);
    }

    /** @test */
    public function it_creates_inventory_transaction_when_deducting_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 100]);

        $this->inventoryService->deductStock($product->id, 25, 'Order fulfillment');

        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->id,
            'quantity' => -25,
            'type' => 'deduction',
        ]);
    }

    /** @test */
    public function it_can_add_stock_quantity()
    {
        $product = Product::factory()->create(['stock_quantity' => 50]);

        $result = $this->inventoryService->addStock($product->id, 30);

        $this->assertTrue($result);
        $product->refresh();
        $this->assertEquals(80, $product->stock_quantity);
    }

    /** @test */
    public function it_creates_inventory_transaction_when_adding_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 50]);

        $this->inventoryService->addStock($product->id, 30, 'Restock');

        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->id,
            'quantity' => 30,
            'type' => 'addition',
        ]);
    }

    /** @test */
    public function it_cannot_deduct_more_stock_than_available()
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $result = $this->inventoryService->deductStock($product->id, 20);

        $this->assertFalse($result);
        $product->refresh();
        $this->assertEquals(10, $product->stock_quantity);
    }

    /** @test */
    public function it_can_get_low_stock_products()
    {
        $product1 = Product::factory()->create(['stock_quantity' => 5, 'low_stock_threshold' => 10]);
        $product2 = Product::factory()->create(['stock_quantity' => 50, 'low_stock_threshold' => 10]);
        $product3 = Product::factory()->create(['stock_quantity' => 3, 'low_stock_threshold' => 10]);

        $lowStockProducts = $this->inventoryService->getLowStockProducts();

        $this->assertCount(2, $lowStockProducts);
        $this->assertTrue($lowStockProducts->contains($product1));
        $this->assertTrue($lowStockProducts->contains($product3));
        $this->assertFalse($lowStockProducts->contains($product2));
    }

    /** @test */
    public function it_can_get_out_of_stock_products()
    {
        $product1 = Product::factory()->create(['stock_quantity' => 0]);
        $product2 = Product::factory()->create(['stock_quantity' => 10]);
        $product3 = Product::factory()->create(['stock_quantity' => 0]);

        $outOfStockProducts = $this->inventoryService->getOutOfStockProducts();

        $this->assertCount(2, $outOfStockProducts);
        $this->assertTrue($outOfStockProducts->contains($product1));
        $this->assertTrue($outOfStockProducts->contains($product3));
        $this->assertFalse($outOfStockProducts->contains($product2));
    }

    /** @test */
    public function it_can_reserve_stock_for_order()
    {
        $product = Product::factory()->create(['stock_quantity' => 100]);

        $result = $this->inventoryService->reserveStock($product->id, 15, 'ORD-123');

        $this->assertTrue($result);
        $this->assertDatabaseHas('inventory_reservations', [
            'product_id' => $product->id,
            'quantity' => 15,
            'reference_id' => 'ORD-123',
        ]);
    }

    /** @test */
    public function it_can_release_reserved_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 100]);
        $this->inventoryService->reserveStock($product->id, 15, 'ORD-123');

        $result = $this->inventoryService->releaseReservedStock($product->id, 'ORD-123');

        $this->assertTrue($result);
        $this->assertDatabaseMissing('inventory_reservations', [
            'product_id' => $product->id,
            'reference_id' => 'ORD-123',
        ]);
    }
}
