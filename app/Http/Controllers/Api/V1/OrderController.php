<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Traits\ApiResponse;
use App\Services\InventoryService;
use App\Jobs\SendOrderNotification;
use App\Jobs\GenerateInvoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        private InventoryService $inventoryService
    ) {
    }

    /**
     * Display a listing of orders
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['customer', 'items.product']);

        // Filter by customer (customers can only see their own orders)
        if (auth()->user()->isCustomer()) {
            $query->where('customer_id', auth()->id());
        } elseif ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 15);
        $orders = $query->paginate($perPage);

        return $this->successResponse($orders, 'Orders retrieved successfully');
    }

    /**
     * Create a new order
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_name' => 'required|string|max:255',
            'shipping_email' => 'required|email',
            'shipping_phone' => 'required|string|max:20',
            'shipping_address' => 'required|string',
            'shipping_city' => 'required|string|max:100',
            'shipping_state' => 'required|string|max:100',
            'shipping_postal_code' => 'required|string|max:20',
            'shipping_country' => 'required|string|max:100',
            'payment_method' => 'required|in:credit_card,debit_card,paypal,bank_transfer,cash_on_delivery',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // Check stock availability
        $unavailable = $this->inventoryService->checkStockAvailability($request->items);
        if (!empty($unavailable)) {
            return $this->errorResponse('Some items are not available in requested quantity', 400, [
                'unavailable_items' => $unavailable
            ]);
        }

        DB::beginTransaction();

        try {
            // Create order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'customer_id' => auth()->id(),
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'shipping_name' => $request->shipping_name,
                'shipping_email' => $request->shipping_email,
                'shipping_phone' => $request->shipping_phone,
                'shipping_address' => $request->shipping_address,
                'shipping_city' => $request->shipping_city,
                'shipping_state' => $request->shipping_state,
                'shipping_postal_code' => $request->shipping_postal_code,
                'shipping_country' => $request->shipping_country,
                'notes' => $request->notes,
                'subtotal' => 0,
                'tax' => 0,
                'shipping_cost' => 0,
                'discount' => 0,
                'total' => 0,
            ]);

            // Create order items
            $subtotal = 0;
            foreach ($request->items as $item) {
                if ($item['product_variant_id'] ?? null) {
                    $variant = ProductVariant::find($item['product_variant_id']);
                    $unitPrice = $variant->getCurrentPrice();
                    $productName = $variant->product->name;
                    $variantName = $variant->name;
                    $sku = $variant->sku;
                } else {
                    $product = Product::find($item['product_id']);
                    $unitPrice = $product->getCurrentPrice();
                    $productName = $product->name;
                    $variantName = null;
                    $sku = $product->sku;
                }

                $itemSubtotal = $unitPrice * $item['quantity'];
                $subtotal += $itemSubtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'product_name' => $productName,
                    'variant_name' => $variantName,
                    'sku' => $sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                ]);
            }

            // Calculate totals
            $tax = $subtotal * 0.1; // 10% tax (configurable)
            $shippingCost = 10.00; // Flat rate (could be calculated based on location)
            $total = $subtotal + $tax + $shippingCost;

            $order->update([
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_cost' => $shippingCost,
                'total' => $total,
            ]);

            // Deduct inventory
            $this->inventoryService->deductInventory($order);

            DB::commit();

            // Dispatch jobs
            SendOrderNotification::dispatch($order, 'created');
            GenerateInvoice::dispatch($order);

            return $this->createdResponse(
                $order->load(['items.product', 'customer']),
                'Order created successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified order
     */
    public function show(string $id): JsonResponse
    {
        $query = Order::with(['customer', 'items.product', 'items.variant', 'invoice']);

        // Customers can only see their own orders
        if (auth()->user()->isCustomer()) {
            $query->where('customer_id', auth()->id());
        }

        $order = $query->find($id);

        if (!$order) {
            return $this->notFoundResponse('Order not found');
        }

        return $this->successResponse($order, 'Order retrieved successfully');
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return $this->notFoundResponse('Order not found');
        }

        // Customers cannot update order status
        if (auth()->user()->isCustomer()) {
            return $this->forbiddenResponse('Customers cannot update order status');
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $newStatus = $request->status;
        $oldStatus = $order->status;

        // Validate status transition
        if (!$order->updateStatus($newStatus)) {
            return $this->errorResponse(
                "Cannot transition from '{$oldStatus}' to '{$newStatus}'",
                400
            );
        }

        // Handle inventory restoration for cancelled orders
        if ($newStatus === 'cancelled') {
            $this->inventoryService->restoreInventory($order);
        }

        // Send notification
        SendOrderNotification::dispatch($order, 'status_updated');

        return $this->successResponse(
            $order->load(['customer', 'items.product']),
            'Order status updated successfully'
        );
    }

    /**
     * Cancel an order
     */
    public function cancel(string $id): JsonResponse
    {
        $query = Order::query();

        // Customers can only cancel their own orders
        if (auth()->user()->isCustomer()) {
            $query->where('customer_id', auth()->id());
        }

        $order = $query->find($id);

        if (!$order) {
            return $this->notFoundResponse('Order not found');
        }

        if (!$order->canBeCancelled()) {
            return $this->errorResponse(
                'Order cannot be cancelled in current status: ' . $order->status,
                400
            );
        }

        try {
            $order->updateStatus('cancelled');
            
            // Restore inventory
            $this->inventoryService->restoreInventory($order);

            // Send notification
            SendOrderNotification::dispatch($order, 'cancelled');

            return $this->successResponse(
                $order->load(['customer', 'items.product']),
                'Order cancelled successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to cancel order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Not used - orders are cancelled, not deleted
     */
    public function destroy(string $id): JsonResponse
    {
        return $this->errorResponse('Orders cannot be deleted, use cancel instead', 405);
    }
}
