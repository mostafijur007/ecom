<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Vendor - Products",
 *     description="Vendor product management endpoints"
 * )
 */
class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
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
     *     path="/vendor/products",
     *     summary="Get vendor's products",
     *     description="Retrieve paginated list of products owned by authenticated vendor",
     *     tags={"Vendor - Products"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter by category ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status (active/inactive)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "inactive"})
     *     ),
     *     @OA\Parameter(
     *         name="featured",
     *         in="query",
     *         description="Filter by featured flag",
     *         required=false,
     *         @OA\Schema(type="boolean")
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
     *         description="Products retrieved successfully",
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
        $filters = $request->only(['category_id', 'status', 'featured', 'min_price', 'max_price', 'in_stock']);
        $perPage = $request->input('per_page', 15);

        $products = $this->productService->getVendorProducts($this->getVendorId(), $filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/vendor/products/{id}",
     *     summary="Get product by ID (Vendor)",
     *     description="Retrieve detailed information about vendor's own product",
     *     tags={"Vendor - Products"},
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
     *         description="Product retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - Not your product"),
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show(int $id): JsonResponse
    {
        // Check ownership
        if (!$this->productService->isOwnedByVendor($id, $this->getVendorId())) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden - You do not own this product',
            ], 403);
        }

        $product = $this->productService->getProductById($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/vendor/products",
     *     summary="Create new product (Vendor)",
     *     description="Create a new product for authenticated vendor",
     *     tags={"Vendor - Products"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ProductRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Force vendor_id to authenticated user
            $product = $this->productService->createProduct($request->all(), $this->getVendorId());

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/vendor/products/{id}",
     *     summary="Update product (Vendor)",
     *     description="Update vendor's own product",
     *     tags={"Vendor - Products"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ProductRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - Not your product"),
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Check ownership
        if (!$this->productService->isOwnedByVendor($id, $this->getVendorId())) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden - You do not own this product',
            ], 403);
        }

        try {
            $product = $this->productService->updateProduct($id, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getMessage() === 'Product not found' ? 404 : 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/vendor/products/{id}",
     *     summary="Delete product (Vendor)",
     *     description="Soft delete vendor's own product",
     *     tags={"Vendor - Products"},
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
     *         description="Product deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - Not your product"),
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        // Check ownership
        if (!$this->productService->isOwnedByVendor($id, $this->getVendorId())) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden - You do not own this product',
            ], 403);
        }

        try {
            $this->productService->deleteProduct($id);

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getMessage() === 'Product not found' ? 404 : 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/vendor/products/search",
     *     summary="Search vendor's products",
     *     description="Full-text search within vendor's own products",
     *     tags={"Vendor - Products"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Search query (minimum 2 characters)",
     *         required=true,
     *         @OA\Schema(type="string", minLength=2)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->input('q', '');
            $perPage = $request->input('per_page', 20);

            // Search all products first
            $results = $this->productService->searchProducts($query, $perPage);

            // Filter by vendor - transform paginator
            $vendorId = $this->getVendorId();
            $filtered = $results->getCollection()->filter(function ($product) use ($vendorId) {
                return $product->vendor_id === $vendorId;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $filtered->values(),
                    'total' => $filtered->count(),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/vendor/products/low-stock",
     *     summary="Get low stock products (Vendor)",
     *     description="Retrieve vendor's products with stock below threshold",
     *     tags={"Vendor - Products"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Low stock products retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Product")
     *             )
     *         )
     *     )
     * )
     */
    public function lowStock(): JsonResponse
    {
        $products = $this->productService->getLowStockProducts($this->getVendorId());

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/vendor/products/bulk-import",
     *     summary="Bulk import products (Vendor)",
     *     description="Import multiple products for authenticated vendor",
     *     tags={"Vendor - Products"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="products",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/ProductRequest")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Import completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $products = $request->input('products', []);
        
        // Force vendor_id for all products
        $result = $this->productService->bulkImportProducts($products, $this->getVendorId());

        return response()->json([
            'success' => true,
            'message' => "Imported {$result['imported']} products, {$result['failed']} failed",
            'data' => $result,
        ]);
    }
}
