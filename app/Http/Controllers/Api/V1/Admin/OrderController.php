<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * @OA\Tag(
 *     name="Admin - Orders",
 *     description="Admin order management endpoints"
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
     * @OA\Get(
     *     path="/admin/orders",
     *     summary="Get all orders (Admin)",
     *     description="Retrieve paginated list of all orders with filtering options",
     *     tags={"Admin - Orders"},
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
     *         name="customer_id",
     *         in="query",
     *         description="Filter by customer ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="vendor_id",
     *         in="query",
     *         description="Filter by vendor ID",
     *         required=false,
     *         @OA\Schema(type="integer")
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
        $filters = $request->only(['status', 'payment_status', 'customer_id', 'vendor_id']);
        $perPage = $request->input('per_page', 15);

        $orders = $this->orderService->getOrders($filters, $perPage);

        return $this->successResponse($orders, 'Orders retrieved successfully');
    }

    /**
     * @OA\Get(
     *     path="/admin/orders/{id}",
     *     summary="Get order by ID (Admin)",
     *     description="Retrieve detailed information about a specific order",
     *     tags={"Admin - Orders"},
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
        $order = $this->orderService->getOrderById($id);

        if (!$order) {
            return $this->notFoundResponse('Order not found');
        }

        return $this->successResponse($order, 'Order retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/admin/orders",
     *     summary="Create new order (Admin)",
     *     description="Create a new order for any customer",
     *     tags={"Admin - Orders"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/OrderRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Order"),
     *             @OA\Property(property="errors", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="errors", type="object")
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
    public function store(Request $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder($request->all());

            return $this->createdResponse($order, 'Order created successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/admin/orders/{id}/status",
     *     summary="Update order status (Admin)",
     *     description="Update the status of any order with workflow validation",
     *     tags={"Admin - Orders"},
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
     *             @OA\Property(property="status", type="string", enum={"pending", "processing", "shipped", "delivered", "cancelled", "returned"}),
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
     *             @OA\Property(property="message", type="string"),
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
        try {
            $status = $request->input('status');
            $notes = $request->input('notes');

            $order = $this->orderService->updateOrderStatus($id, $status, $notes);

            return $this->successResponse($order, 'Order status updated successfully');
        } catch (\Exception $e) {
            $statusCode = $e->getMessage() === 'Order not found' ? 404 : 422;
            return $this->errorResponse($e->getMessage(), null, $statusCode);
        }
    }

    /**
     * @OA\Patch(
     *     path="/admin/orders/{id}/payment",
     *     summary="Update payment status (Admin)",
     *     description="Update the payment status of any order",
     *     tags={"Admin - Orders"},
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
     *             required={"payment_status"},
     *             @OA\Property(property="payment_status", type="string", enum={"pending", "paid", "failed", "refunded"}),
     *             @OA\Property(property="transaction_id", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment status updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Order"),
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
    public function updatePayment(Request $request, int $id): JsonResponse
    {
        try {
            $paymentStatus = $request->input('payment_status');
            $transactionId = $request->input('transaction_id');

            $order = $this->orderService->updatePaymentStatus($id, $paymentStatus, $transactionId);

            return $this->successResponse($order, 'Payment status updated successfully');
        } catch (\Exception $e) {
            $statusCode = $e->getMessage() === 'Order not found' ? 404 : 500;
            return $this->errorResponse($e->getMessage(), null, $statusCode);
        }
    }

    /**
     * @OA\Post(
     *     path="/admin/orders/{id}/cancel",
     *     summary="Cancel order (Admin)",
     *     description="Cancel any order and restore inventory",
     *     tags={"Admin - Orders"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="reason", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order cancelled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order cancelled successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Order"),
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
     *         description="Cannot cancel order",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string"),
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
    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $reason = $request->input('reason');
            $order = $this->orderService->cancelOrder($id, $reason);

            return $this->successResponse($order, 'Order cancelled successfully');
        } catch (\Exception $e) {
            $statusCode = $e->getMessage() === 'Order not found' ? 404 : 422;
            return $this->errorResponse($e->getMessage(), null, $statusCode);
        }
    }

    /**
     * @OA\Get(
     *     path="/admin/orders/pending",
     *     summary="Get pending orders (Admin)",
     *     description="Retrieve all pending orders",
     *     tags={"Admin - Orders"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Maximum number of orders to return",
     *         required=false,
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pending orders retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Pending orders retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Order")
     *             ),
     *             @OA\Property(property="errors", type="null", example=null)
     *         )
     *     )
     * )
     */
    public function pending(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 50);
        $orders = $this->orderService->getPendingOrders($limit);

        return $this->successResponse($orders, 'Pending orders retrieved successfully');
    }

    /**
     * @OA\Get(
     *     path="/admin/orders/statistics",
     *     summary="Get order statistics (Admin)",
     *     description="Retrieve comprehensive order statistics across all vendors",
     *     tags={"Admin - Orders"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="vendor_id",
     *         in="query",
     *         description="Filter statistics by vendor ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
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
     *     )
     * )
     */
    public function statistics(Request $request): JsonResponse
    {
        $vendorId = $request->input('vendor_id');
        $statistics = $this->orderService->getStatistics($vendorId);

        return $this->successResponse($statistics, 'Order statistics retrieved successfully');
    }

    /**
     * @OA\Get(
     *     path="/admin/orders/{id}/invoice",
     *     summary="Download order invoice (Admin)",
     *     description="Download the PDF invoice for a specific order",
     *     tags={"Admin - Orders"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="download",
     *         in="query",
     *         description="Set to true to force download, false to view inline",
     *         required=false,
     *         @OA\Schema(type="boolean", default=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice PDF file",
     *         @OA\MediaType(
     *             mediaType="application/pdf"
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order or invoice not found"
     *     )
     * )
     */
    public function downloadInvoice(int $id, Request $request): HttpResponse
    {
        try {
            $order = $this->orderService->getOrderById($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                    'data' => null,
                    'errors' => ['order' => ['Order not found']]
                ], 404);
            }

            $invoice = $order->invoice;

            if (!$invoice || !$invoice->pdf_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found or not yet generated',
                    'data' => null,
                    'errors' => ['invoice' => ['Invoice not found or not yet generated']]
                ], 404);
            }

            if (!Storage::disk('public')->exists($invoice->pdf_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice PDF file not found',
                    'data' => null,
                    'errors' => ['file' => ['Invoice PDF file not found in storage']]
                ], 404);
            }

            $pdfContent = Storage::disk('public')->get($invoice->pdf_path);
            $filename = $invoice->invoice_number . '.pdf';
            $download = $request->boolean('download', true);

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', ($download ? 'attachment' : 'inline') . '; filename="' . $filename . '"');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoice',
                'data' => null,
                'errors' => ['error' => [$e->getMessage()]]
            ], 500);
        }
    }
}
