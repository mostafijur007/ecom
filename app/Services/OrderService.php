<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Jobs\SendOrderNotification;
use App\Jobs\GenerateInvoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private ProductRepositoryInterface $productRepository,
        private InventoryService $inventoryService
    ) {
    }

    /**
     * Get paginated orders with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getOrders(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->getAllWithFilters($filters, $perPage);
    }

    /**
     * Get order by ID
     *
     * @param int $id
     * @return Order|null
     */
    public function getOrderById(int $id): ?Order
    {
        return $this->orderRepository->findWithRelations($id);
    }

    /**
     * Create a new order with inventory management
     *
     * @param array $data
     * @param int|null $userId Override user ID for admin
     * @return Order
     * @throws ValidationException
     * @throws \Exception
     */
    public function createOrder(array $data, ?int $userId = null): Order
    {
        $this->validateOrderData($data);

        // Set user ID if provided
        if ($userId) {
            $data['user_id'] = $userId;
        }

        return DB::transaction(function () use ($data) {
            // Validate and calculate order totals
            $orderData = $this->prepareOrderData($data);

            // Check stock availability for all items
            $stockCheck = $this->inventoryService->checkStockAvailability($data['items']);
            if (!empty($stockCheck)) {
                $productIds = array_column($stockCheck, 'product_id');
                throw new \Exception("Insufficient stock for products: " . implode(', ', $productIds));
            }

            // Create order
            $order = $this->orderRepository->create($orderData);

            // Create order items
            foreach ($data['items'] as $item) {
                $product = $this->productRepository->findWithRelations($item['product_id']);

                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $product->sale_price ?? $product->price,
                    'subtotal' => ($product->sale_price ?? $product->price) * $item['quantity'],
                    'vendor_id' => $product->vendor_id,
                ]);
            }

            // Deduct inventory for entire order
            $this->inventoryService->deductInventory($order);

            // Reload order with relationships
            $order->load(['items.product', 'user', 'shippingAddress']);

            // Dispatch notification
            SendOrderNotification::dispatch($order, 'created');

            return $order;
        });
    }

    /**
     * Update order status with workflow validation
     *
     * @param int $id
     * @param string $status
     * @param string|null $notes
     * @return Order
     * @throws \Exception
     */
    public function updateOrderStatus(int $id, string $status, ?string $notes = null): Order
    {
        $order = $this->orderRepository->findWithRelations($id);

        if (!$order) {
            throw new \Exception('Order not found');
        }

        // Validate status transition
        $this->validateStatusTransition($order->status, $status);

        $updateData = ['status' => $status];
        if ($notes) {
            $updateData['notes'] = $notes;
        }

        // Update order
        $order = $this->orderRepository->update($order, $updateData);

        // Handle status-specific logic
        $this->handleStatusChange($order, $status);

        // Dispatch notification
        SendOrderNotification::dispatch($order, 'status_updated');

        return $order;
    }

    /**
     * Update payment status
     *
     * @param int $id
     * @param string $paymentStatus
     * @param string|null $transactionId
     * @return Order
     * @throws \Exception
     */
    public function updatePaymentStatus(int $id, string $paymentStatus, ?string $transactionId = null): Order
    {
        $order = $this->orderRepository->findWithRelations($id);

        if (!$order) {
            throw new \Exception('Order not found');
        }

        $updateData = ['payment_status' => $paymentStatus];
        
        if ($transactionId) {
            $updateData['transaction_id'] = $transactionId;
        }

        if ($paymentStatus === 'paid') {
            $updateData['paid_at'] = now();

            // Generate invoice after payment
            GenerateInvoice::dispatch($order);
        }

        $order = $this->orderRepository->update($order, $updateData);

        // Dispatch notification
        SendOrderNotification::dispatch($order, 'payment_updated');

        return $order;
    }

    /**
     * Cancel order and restore inventory
     *
     * @param int $id
     * @param string|null $reason
     * @return Order
     * @throws \Exception
     */
    public function cancelOrder(int $id, ?string $reason = null): Order
    {
        $order = $this->orderRepository->findWithRelations($id);

        if (!$order) {
            throw new \Exception('Order not found');
        }

        // Only pending or processing orders can be cancelled
        if (!in_array($order->status, ['pending', 'processing'])) {
            throw new \Exception("Cannot cancel order with status: {$order->status}");
        }

        return DB::transaction(function () use ($order, $reason) {
            // Restore inventory for entire order
            $this->inventoryService->restoreInventory($order);

            // Update order status
            $updateData = [
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ];

            if ($reason) {
                $updateData['notes'] = $reason;
            }

            $order = $this->orderRepository->update($order, $updateData);

            // Dispatch notification
            SendOrderNotification::dispatch($order, 'cancelled');

            return $order;
        });
    }

    /**
     * Get customer orders
     *
     * @param int $customerId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getCustomerOrders(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->getByCustomer($customerId, [], $perPage);
    }

    /**
     * Get vendor orders
     *
     * @param int $vendorId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getVendorOrders(int $vendorId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->getByVendor($vendorId, $filters, $perPage);
    }

    /**
     * Get pending orders
     *
     * @param int $limit
     * @return Collection
     */
    public function getPendingOrders(int $limit = 50): Collection
    {
        return $this->orderRepository->getPending($limit);
    }

    /**
     * Get recent orders
     *
     * @param int $days
     * @param int $limit
     * @return Collection
     */
    public function getRecentOrders(int $days = 7, int $limit = 20): Collection
    {
        return $this->orderRepository->getRecent($days, $limit);
    }

    /**
     * Calculate sales for period
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param int|null $vendorId
     * @return float
     */
    public function calculateSales(\DateTime $startDate, \DateTime $endDate, ?int $vendorId = null): float
    {
        $fromDate = $startDate->format('Y-m-d H:i:s');
        $toDate = $endDate->format('Y-m-d H:i:s');
        
        return $this->orderRepository->calculateSales($fromDate, $toDate, $vendorId);
    }

    /**
     * Get order statistics
     *
     * @param int|null $vendorId
     * @return array
     */
    public function getStatistics(?int $vendorId = null): array
    {
        return $this->orderRepository->getStatistics($vendorId);
    }

    /**
     * Check if vendor owns order (at least one item)
     *
     * @param int $orderId
     * @param int $vendorId
     * @return bool
     */
    public function isOwnedByVendor(int $orderId, int $vendorId): bool
    {
        $order = $this->orderRepository->findWithRelations($orderId);
        if (!$order) {
            return false;
        }

        return $order->items()->where('vendor_id', $vendorId)->exists();
    }

    /**
     * Prepare order data with calculations
     *
     * @param array $data
     * @return array
     */
    private function prepareOrderData(array $data): array
    {
        $subtotal = 0;

        foreach ($data['items'] as $item) {
            $product = $this->productRepository->findWithRelations($item['product_id']);
            $price = $product->sale_price ?? $product->price;
            $subtotal += $price * $item['quantity'];
        }

        $shippingCost = $data['shipping_cost'] ?? 0;
        $discount = $data['discount'] ?? 0;
        $tax = $data['tax'] ?? ($subtotal * 0.1); // 10% default tax

        $total = $subtotal + $shippingCost + $tax - $discount;

        return [
            'user_id' => $data['user_id'],
            'order_number' => $this->generateOrderNumber(),
            'status' => 'pending',
            'payment_method' => $data['payment_method'] ?? 'cod',
            'payment_status' => $data['payment_status'] ?? 'pending',
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping_cost' => $shippingCost,
            'discount' => $discount,
            'total' => $total,
            'notes' => $data['notes'] ?? null,
            'shipping_address' => $data['shipping_address'] ?? null,
            'billing_address' => $data['billing_address'] ?? null,
        ];
    }

    /**
     * Generate unique order number
     *
     * @return string
     */
    private function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
    }

    /**
     * Validate status transition
     *
     * @param string $currentStatus
     * @param string $newStatus
     * @return void
     * @throws \Exception
     */
    private function validateStatusTransition(string $currentStatus, string $newStatus): void
    {
        $validTransitions = [
            'pending' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered', 'returned'],
            'delivered' => ['returned'],
            'cancelled' => [],
            'returned' => [],
        ];

        if (!isset($validTransitions[$currentStatus])) {
            throw new \Exception("Invalid current status: {$currentStatus}");
        }

        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            throw new \Exception("Cannot transition from {$currentStatus} to {$newStatus}");
        }
    }

    /**
     * Handle status-specific logic
     *
     * @param Order $order
     * @param string $status
     * @return void
     */
    private function handleStatusChange(Order $order, string $status): void
    {
        switch ($status) {
            case 'shipped':
                // Generate invoice when shipped
                GenerateInvoice::dispatch($order);
                break;

            case 'delivered':
                // Mark as delivered
                $this->orderRepository->update($order, ['delivered_at' => now()]);
                break;

            case 'returned':
                // Restore inventory on return
                $this->inventoryService->restoreInventory($order);
                break;
        }
    }

    /**
     * Validate order data
     *
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    private function validateOrderData(array $data): void
    {
        $rules = [
            'user_id' => 'required|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'nullable|string|in:cod,credit_card,debit_card,paypal,stripe',
            'payment_status' => 'nullable|string|in:pending,paid,failed,refunded',
            'shipping_cost' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'shipping_address' => 'nullable|array',
            'billing_address' => 'nullable|array',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
