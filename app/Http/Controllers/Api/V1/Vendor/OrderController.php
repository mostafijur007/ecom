<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Traits\ApiResponse;
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
    use ApiResponse;

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
     *     path="/vendor/orders",
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
     *             @OA\Property(property="message", type="string", example="Orders retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(ref="#/components/schemas/Order")
     *                 ),
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             ),
     *             @OA\Property(property="errors", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="errors", type="null", example=null)
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'payment_status']);
        $perPage = $request->input('per_page', 15);

        $orders = $this->orderService->getVendorOrders($this->getVendorId(), $filters, $perPage);

        return $this->successResponse($orders, 'Orders retrieved successfully');
    }

    /**
     * @OA\Get(
     *     path="/vendor/orders/{id}",
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
     *             @OA\Property(property="message", type="string", example="Order retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Order"),
     *             @OA\Property(property="errors", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Not your order",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="This order does not contain your products"),
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="errors", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order not found"),
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="errors", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="errors", type="null", example=null)
     *         )
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        // Check ownership
        if (!$this->orderService->isOwnedByVendor($id, $this->getVendorId())) {
            return $this->forbiddenResponse('This order does not contain your products');
        }

        $order = $this->orderService->getOrderById($id);

        if (!$order) {
            return $this->notFoundResponse('Order not found');
        }

        return $this->successResponse($order, 'Order retrieved successfully');
    }

    /**
     * @OA\Patch(
     *     path="/vendor/orders/{id}/status",
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
     *             @OA\Property(property="data", ref="#/components/schemas/Order"),
     *             @OA\Property(property="errors", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Not your order or invalid status",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Vendors can only update status to processing or shipped"),
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="errors", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order not found"),
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="errors", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid status transition",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot transition from pending to delivered"),
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="errors", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="errors", type="null", example=null)
     *         )
     *     )
     * )
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        // Check ownership
        if (!$this->orderService->isOwnedByVendor($id, $this->getVendorId())) {
            return $this->forbiddenResponse('This order does not contain your products');
        }

        try {
            $status = $request->input('status');
            $notes = $request->input('notes');

            // Vendors can only set to processing or shipped
            if (!in_array($status, ['processing', 'shipped'])) {
                return $this->forbiddenResponse('Vendors can only update status to processing or shipped');
            }

            $order = $this->orderService->updateOrderStatus($id, $status, $notes);

            return $this->successResponse($order, 'Order status updated successfully');
        } catch (\Exception $e) {
            $statusCode = $e->getMessage() === 'Order not found' ? 404 : 422;
            return $this->errorResponse($e->getMessage(), null, $statusCode);
        }
    }

    /**
     * @OA\Get(
     *     path="/vendor/orders/statistics",
     *     summary="Get vendor's order statistics",
     *     description="Retrieve order statistics for vendor's products only",
     *     tags={"Vendor - Orders"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistics retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order statistics retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_orders", type="integer"),
     *                 @OA\Property(property="total_revenue", type="number"),
     *                 @OA\Property(property="average_order_value", type="number"),
     *                 @OA\Property(property="pending_count", type="integer"),
     *                 @OA\Property(property="processing_count", type="integer"),
     *                 @OA\Property(property="shipped_count", type="integer"),
     *                 @OA\Property(property="delivered_count", type="integer"),
     *                 @OA\Property(property="cancelled_count", type="integer")
     *             ),
     *             @OA\Property(property="errors", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="errors", type="null", example=null)
     *         )
     *     )
     * )
     */
    public function statistics(): JsonResponse
    {
        $statistics = $this->orderService->getStatistics($this->getVendorId());

        return $this->successResponse($statistics, 'Order statistics retrieved successfully');
    }

    /**
     * @OA\Get(
     *     path="/vendor/orders/recent",
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
     *             @OA\Property(property="message", type="string", example="Recent orders retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Order")
     *             ),
     *             @OA\Property(property="errors", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="errors", type="null", example=null)
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
            return $order->items()->whereHas('product', function ($query) use ($vendorId) {
                $query->where('vendor_id', $vendorId);
            })->exists();
        });

        return $this->successResponse($vendorOrders->values(), 'Recent orders retrieved successfully');
    }
}
