<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    use ApiResponse;

    public function __construct(
        private OrderService $orderService,
        private ProductService $productService
    ) {
    }

    /**
     * Get customer dashboard
     * 
     * @OA\Get(
     *     path="/customer/dashboard",
     *     summary="Get customer dashboard statistics",
     *     tags={"Customer"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer dashboard data retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="customer_id", type="integer"),
     *                 @OA\Property(property="customer_name", type="string"),
     *                 @OA\Property(property="customer_email", type="string"),
     *                 @OA\Property(property="total_orders", type="integer"),
     *                 @OA\Property(property="pending_orders", type="integer"),
     *                 @OA\Property(property="processing_orders", type="integer"),
     *                 @OA\Property(property="completed_orders", type="integer"),
     *                 @OA\Property(property="cancelled_orders", type="integer"),
     *                 @OA\Property(property="recent_orders", type="array", @OA\Items(type="object"))
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
    public function dashboard(): JsonResponse
    {
        $user = auth('api')->user();

        // Get customer statistics from OrderService
        $statistics = $this->orderService->getStatistics();
        $customerOrders = $this->orderService->getCustomerOrders($user->id, 999);
        
        // Calculate customer-specific stats
        $totalOrders = $customerOrders->total();
        $pendingOrders = $customerOrders->where('status', 'pending')->count();
        $processingOrders = $customerOrders->where('status', 'processing')->count();
        $completedOrders = $customerOrders->where('status', 'completed')->count();
        $cancelledOrders = $customerOrders->where('status', 'cancelled')->count();

        return $this->successResponse([
            'customer_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'total_orders' => $totalOrders,
            'pending_orders' => $pendingOrders,
            'processing_orders' => $processingOrders,
            'completed_orders' => $completedOrders,
            'cancelled_orders' => $cancelledOrders,
            'recent_orders' => $customerOrders->take(5)->values(),
        ], 'Customer dashboard data retrieved successfully');
    }

    /**
     * Place a new order
     * 
     * @OA\Post(
     *     path="/customer/orders",
     *     summary="Place a new order",
     *     tags={"Customer"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"items", "shipping_address", "payment_method"},
     *             @OA\Property(property="items", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="product_id", type="integer", example=1),
     *                     @OA\Property(property="variant_id", type="integer", example=1, nullable=true),
     *                     @OA\Property(property="quantity", type="integer", example=2)
     *                 )
     *             ),
     *             @OA\Property(property="shipping_address", type="object",
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="phone", type="string", example="+1234567890"),
     *                 @OA\Property(property="address", type="string", example="123 Main St"),
     *                 @OA\Property(property="city", type="string", example="New York"),
     *                 @OA\Property(property="state", type="string", example="NY"),
     *                 @OA\Property(property="postal_code", type="string", example="10001"),
     *                 @OA\Property(property="country", type="string", example="USA")
     *             ),
     *             @OA\Property(property="payment_method", type="string", enum={"credit_card", "debit_card", "paypal", "bank_transfer", "cash_on_delivery"}),
     *             @OA\Property(property="notes", type="string", example="Please deliver before 5 PM", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order placed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order placed successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="order", type="object")
     *             ),
     *             @OA\Property(property="errors", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Insufficient stock or other error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Insufficient stock for products: 1, 2"),
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
     *     )
     * )
     */
    public function placeOrder(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        // Validate order data
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|array',
            'shipping_address.name' => 'required|string|max:255',
            'shipping_address.email' => 'required|email|max:255',
            'shipping_address.phone' => 'required|string|max:20',
            'shipping_address.address' => 'required|string',
            'shipping_address.city' => 'required|string|max:100',
            'shipping_address.state' => 'required|string|max:100',
            'shipping_address.postal_code' => 'required|string|max:20',
            'shipping_address.country' => 'required|string|max:100',
            'payment_method' => 'required|in:credit_card,debit_card,paypal,bank_transfer,cash_on_delivery',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            // Create order using OrderService
            $order = $this->orderService->createOrder($request->all(), $user->id);
            
            return $this->createdResponse([
                'order' => $order->load(['items.product', 'items.variant']),
            ], 'Order placed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get customer's order history
     * 
     * @OA\Get(
     *     path="/customer/orders",
     *     summary="Get customer order history",
     *     tags={"Customer"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of orders per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order history retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order history retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="orders", type="object",
     *                     @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="last_page", type="integer"),
     *                     @OA\Property(property="per_page", type="integer"),
     *                     @OA\Property(property="total", type="integer")
     *                 )
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
    public function orderHistory(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $perPage = $request->get('per_page', 15);

        try {
            $orders = $this->orderService->getCustomerOrders($user->id, $perPage);
            
            return $this->successResponse([
                'orders' => $orders,
            ], 'Order history retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get order details
     * 
     * @OA\Get(
     *     path="/customer/orders/{id}",
     *     summary="Get order details",
     *     tags={"Customer"},
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
     *         description="Order details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order details retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="order", type="object")
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
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You do not have permission to view this order"),
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
     *     )
     * )
     */
    public function orderDetails($id): JsonResponse
    {
        $user = auth('api')->user();

        try {
            $order = $this->orderService->getOrderById($id);
            
            if (!$order) {
                return $this->notFoundResponse('Order not found');
            }

            // Check if order belongs to this customer
            if ($order->customer_id !== $user->id) {
                return $this->forbiddenResponse('You do not have permission to view this order');
            }

            return $this->successResponse([
                'order' => $order->load(['items.product', 'items.variant', 'invoice']),
            ], 'Order details retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Cancel an order
     * 
     * @OA\Delete(
     *     path="/customer/orders/{id}",
     *     summary="Cancel an order",
     *     tags={"Customer"},
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
     *             @OA\Property(property="reason", type="string", example="Changed my mind", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order cancelled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order cancelled successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="order", type="object")
     *             ),
     *             @OA\Property(property="errors", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Cannot cancel order",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot cancel order with status: completed"),
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
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You do not have permission to cancel this order"),
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
     *     )
     * )
     */
    public function cancelOrder(Request $request, $id): JsonResponse
    {
        $user = auth('api')->user();

        try {
            $order = $this->orderService->getOrderById($id);
            
            if (!$order) {
                return $this->notFoundResponse('Order not found');
            }

            // Check if order belongs to this customer
            if ($order->customer_id !== $user->id) {
                return $this->forbiddenResponse('You do not have permission to cancel this order');
            }

            // Cancel the order
            $reason = $request->input('reason', 'Cancelled by customer');
            $cancelledOrder = $this->orderService->cancelOrder($id, $reason);

            return $this->successResponse([
                'order' => $cancelledOrder,
            ], 'Order cancelled successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * View customer profile
     * 
     * @OA\Get(
     *     path="/customer/profile",
     *     summary="Get customer profile",
     *     tags={"Customer"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Customer profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer profile retrieved"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object")
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
    public function profile(): JsonResponse
    {
        $user = auth('api')->user();

        return $this->successResponse(['user' => $user], 'Customer profile retrieved');
    }

    /**
     * Update customer profile
     * 
     * @OA\Put(
     *     path="/customer/profile",
     *     summary="Update customer profile",
     *     tags={"Customer"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890", nullable=true),
     *             @OA\Property(property="address", type="string", example="123 Main St", nullable=true),
     *             @OA\Property(property="city", type="string", example="New York", nullable=true),
     *             @OA\Property(property="state", type="string", example="NY", nullable=true),
     *             @OA\Property(property="postal_code", type="string", example="10001", nullable=true),
     *             @OA\Property(property="country", type="string", example="USA", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object")
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
     *     )
     * )
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        // Validate profile data
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $user->update($request->only([
                'name', 'email', 'phone', 'address', 
                'city', 'state', 'postal_code', 'country'
            ]));

            return $this->successResponse([
                'user' => $user->fresh(),
            ], 'Profile updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Browse all products
     * 
     * @OA\Get(
     *     path="/products",
     *     summary="Browse all products",
     *     tags={"Customer"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter by category ID",
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
     *         name="min_price",
     *         in="query",
     *         description="Minimum price",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         description="Maximum price",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="is_featured",
     *         in="query",
     *         description="Filter featured products",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of products per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Products retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Products retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="products", type="object",
     *                     @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="last_page", type="integer"),
     *                     @OA\Property(property="per_page", type="integer"),
     *                     @OA\Property(property="total", type="integer")
     *                 )
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
    public function browseProducts(Request $request): JsonResponse
    {
        try {
            $filters = [
                'category_id' => $request->get('category_id'),
                'vendor_id' => $request->get('vendor_id'),
                'min_price' => $request->get('min_price'),
                'max_price' => $request->get('max_price'),
                'status' => 'active', // Only show active products
                'is_featured' => $request->get('is_featured'),
            ];

            $perPage = $request->get('per_page', 15);
            $products = $this->productService->getProducts(array_filter($filters), $perPage);

            return $this->successResponse([
                'products' => $products,
            ], 'Products retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get product details
     * 
     * @OA\Get(
     *     path="/products/{id}",
     *     summary="Get product details",
     *     tags={"Customer"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product details retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="product", type="object")
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
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product not found"),
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="errors", type="null", example=null)
     *         )
     *     )
     * )
     */
    public function getProduct($id): JsonResponse
    {
        try {
            $product = $this->productService->getProductById($id);

            if (!$product) {
                return $this->notFoundResponse('Product not found');
            }

            // Increment view count
            $this->productService->incrementViews($id);

            return $this->successResponse([
                'product' => $product,
            ], 'Product details retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Search products
     * 
     * @OA\Get(
     *     path="/products/search",
     *     summary="Search products",
     *     tags={"Customer"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Search query",
     *         required=true,
     *         @OA\Schema(type="string", minLength=2)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of products per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Search results retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="products", type="object",
     *                     @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="last_page", type="integer"),
     *                     @OA\Property(property="per_page", type="integer"),
     *                     @OA\Property(property="total", type="integer")
     *                 ),
     *                 @OA\Property(property="query", type="string")
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
     *     )
     * )
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $query = $request->get('q');
            $perPage = $request->get('per_page', 20);
            
            $products = $this->productService->searchProducts($query, $perPage);

            return $this->successResponse([
                'products' => $products,
                'query' => $query,
            ], 'Search results retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get featured products
     * 
     * @OA\Get(
     *     path="/products/featured",
     *     summary="Get featured products",
     *     tags={"Customer"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Maximum number of products to return",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Featured products retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Featured products retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="products", type="array", @OA\Items(type="object"))
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
    public function featuredProducts(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $products = $this->productService->getFeaturedProducts($limit);

            return $this->successResponse([
                'products' => $products,
            ], 'Featured products retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
