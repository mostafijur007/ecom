<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Vendor - Orders",
 *     description="Vendor order management endpoints"
 * )
 */
class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {
    }

    /**
     * Get authenticated vendor ID
     */
    private function getVendorId(): int
    {
        return (int) auth()->id();
    }

    /**
     * @OA\Get(
     *     path="/api/vendor/orders",
     *     summary="Get vendor's orders",
     *     description="Retrieve paginated list of orders containing vendor's products",
     *     tags={"Vendor - Orders"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by order status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "processing", "shipped", "delivered", "cancelled", "returned"})
     *     ),
     *     @OA\Parameter(
     *         name="payment_status",
     *         in="query",
     *         description="Filter by payment status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "paid", "failed", "refunded"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Orders retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'payment_status']);
        $perPage = $request->input('per_page', 15);

        $orders = $this->orderService->getVendorOrders($this->getVendorId(), $filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/vendor/orders/{id}",
     *     summary="Get order by ID (Vendor)",
     *     description="Retrieve detailed information about order containing vendor's products",
     *     tags={"Vendor - Orders"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Order")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - Not your order"),
     *     @OA\Response(response=404, description="Order not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show(int $id): JsonResponse
    {
        // Check ownership
        if (!$this->orderService->isOwnedByVendor($id, $this->getVendorId())) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden - This order does not contain your products',
            ], 403);
        }

        $order = $this->orderService->getOrderById($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/api/vendor/orders/{id}/status",
     *     summary="Update order status (Vendor)",
     *     description="Update status of order containing vendor's products (limited transitions)",
     *     tags={"Vendor - Orders"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"processing", "shipped"}),
     *             @OA\Property(property="notes", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order status updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Order")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - Not your order or invalid status"),
     *     @OA\Response(response=404, description="Order not found"),
     *     @OA\Response(response=422, description="Invalid status transition"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        // Check ownership
        if (!$this->orderService->isOwnedByVendor($id, $this->getVendorId())) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden - This order does not contain your products',
            ], 403);
        }

        try {
            $status = $request->input('status');
            $notes = $request->input('notes');

            // Vendors can only set to processing or shipped
            if (!in_array($status, ['processing', 'shipped'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendors can only update status to processing or shipped',
                ], 403);
            }

            $order = $this->orderService->updateOrderStatus($id, $status, $notes);

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getMessage() === 'Order not found' ? 404 : 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/vendor/orders/statistics",
     *     summary="Get vendor's order statistics",
     *     description="Retrieve order statistics for vendor's products only",
     *     tags={"Vendor - Orders"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistics retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_orders", type="integer"),
     *                 @OA\Property(property="total_revenue", type="number"),
     *                 @OA\Property(property="average_order_value", type="number"),
     *                 @OA\Property(property="pending_count", type="integer"),
     *                 @OA\Property(property="processing_count", type="integer"),
     *                 @OA\Property(property="shipped_count", type="integer"),
     *                 @OA\Property(property="delivered_count", type="integer"),
     *                 @OA\Property(property="cancelled_count", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function statistics(): JsonResponse
    {
        $statistics = $this->orderService->getStatistics($this->getVendorId());

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/vendor/orders/recent",
     *     summary="Get vendor's recent orders",
     *     description="Retrieve recent orders containing vendor's products",
     *     tags={"Vendor - Orders"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="Number of days to look back",
     *         required=false,
     *         @OA\Schema(type="integer", default=7)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Maximum number of orders to return",
     *         required=false,
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recent orders retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Order")
     *             )
     *         )
     *     )
     * )
     */
    public function recent(Request $request): JsonResponse
    {
        $days = $request->input('days', 7);
        $limit = $request->input('limit', 20);
        
        $orders = $this->orderService->getRecentOrders($days, $limit);
        
        // Filter by vendor
        $vendorId = $this->getVendorId();
        $vendorOrders = $orders->filter(function ($order) use ($vendorId) {
            return $order->items()->where('vendor_id', $vendorId)->exists();
        });

        return response()->json([
            'success' => true,
            'data' => $vendorOrders->values(),
        ]);
    }
}
