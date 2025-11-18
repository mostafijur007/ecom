<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Inventory;
use App\Models\Order;
use App\Jobs\LowStockAlert;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Track inventory transaction
     */
    public function trackInventory(
        int $productId,
        string $transactionType,
        int $quantity,
        ?int $variantId = null,
        ?int $orderId = null,
        ?string $reference = null,
        ?string $notes = null
    ): Inventory {
        // Get current balance
        $currentBalance = $this->getAvailableStock($productId, $variantId);
        $newBalance = $currentBalance + $quantity; // quantity can be negative

        // Create inventory transaction
        $inventory = Inventory::create([
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'transaction_type' => $transactionType,
            'quantity' => $quantity,
            'balance_after' => $newBalance,
            'order_id' => $orderId,
            'reference' => $reference,
            'notes' => $notes,
            'user_id' => auth()->id(),
        ]);

        // Update product/variant stock
        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            $variant->stock_quantity = $newBalance;
            $variant->save();
        } else {
            $product = Product::find($productId);
            $product->stock_quantity = $newBalance;
            $product->save();

            // Check for low stock
            if ($product->isLowStock()) {
                $this->checkLowStock($product);
            }
        }

        return $inventory;
    }

    /**
     * Deduct inventory for order
     */
    public function deductInventory(Order $order): bool
    {
        DB::beginTransaction();

        try {
            foreach ($order->items as $item) {
                $this->trackInventory(
                    productId: $item->product_id,
                    transactionType: 'sale',
                    quantity: -$item->quantity, // negative for deduction
                    variantId: $item->product_variant_id,
                    orderId: $order->id,
                    reference: $order->order_number,
                    notes: 'Inventory deducted for order'
                );
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * Restore inventory when order is cancelled
     */
    public function restoreInventory(Order $order): bool
    {
        DB::beginTransaction();

        try {
            foreach ($order->items as $item) {
                $this->trackInventory(
                    productId: $item->product_id,
                    transactionType: 'return',
                    quantity: $item->quantity, // positive for restoration
                    variantId: $item->product_variant_id,
                    orderId: $order->id,
                    reference: $order->order_number,
                    notes: 'Inventory restored - order cancelled'
                );
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * Get available stock for a product or variant
     */
    public function getAvailableStock(int $productId, ?int $variantId = null): int
    {
        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            return $variant ? $variant->stock_quantity : 0;
        }

        $product = Product::find($productId);
        return $product ? $product->stock_quantity : 0;
    }

    /**
     * Check if product is low on stock and dispatch alert
     */
    public function checkLowStock(Product $product): void
    {
        if ($product->isLowStock()) {
            // Dispatch low stock alert job
            LowStockAlert::dispatch($product);
        }
    }

    /**
     * Check stock availability before order placement
     */
    public function checkStockAvailability(array $items): array
    {
        $unavailable = [];

        foreach ($items as $item) {
            $available = $this->getAvailableStock(
                $item['product_id'],
                $item['product_variant_id'] ?? null
            );

            if ($available < $item['quantity']) {
                $unavailable[] = [
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['product_variant_id'] ?? null,
                    'requested' => $item['quantity'],
                    'available' => $available,
                ];
            }
        }

        return $unavailable;
    }

    /**
     * Get inventory history for a product
     */
    public function getInventoryHistory(int $productId, ?int $variantId = null, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        $query = Inventory::with(['user', 'order'])
            ->where('product_id', $productId);

        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        }

        return $query->latest()->limit($limit)->get();
    }
}
