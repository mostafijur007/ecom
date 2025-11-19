<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Admin - Products",
 *     description="Admin product management endpoints"
 * )
 */
class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {
    }

    /**
     * @OA\Get(
     *     path="/admin/products",
     *     summary="Get all products (Admin)",
     *     description="Retrieve paginated list of all products with filtering options",
     *     tags={"Admin - Products"},
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
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(ref="#/components/schemas/Product")
     *                 ),
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category_id', 'vendor_id', 'status', 'featured', 'min_price', 'max_price', 'in_stock']);
        $perPage = $request->input('per_page', 15);

        $products = $this->productService->getProducts($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/admin/products/{id}",
     *     summary="Get product by ID (Admin)",
     *     description="Retrieve detailed information about a specific product",
     *     tags={"Admin - Products"},
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
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        // Increment views
        $this->productService->incrementViews($id);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/admin/products",
     *     summary="Create new product (Admin)",
     *     description="Create a new product for any vendor",
     *     tags={"Admin - Products"},
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
            $product = $this->productService->createProduct($request->all());

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
     *     path="/admin/products/{id}",
     *     summary="Update product (Admin)",
     *     description="Update any product",
     *     tags={"Admin - Products"},
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
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
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
     *     path="/admin/products/{id}",
     *     summary="Delete product (Admin)",
     *     description="Soft delete any product",
     *     tags={"Admin - Products"},
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
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
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
     *     path="/admin/products/search",
     *     summary="Search products (Admin)",
     *     description="Full-text search across product name, description, SKU",
     *     tags={"Admin - Products"},
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

            $results = $this->productService->searchProducts($query, $perPage);

            return response()->json([
                'success' => true,
                'data' => $results,
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
     * @OA\Post(
     *     path="/admin/products/bulk-import",
     *     summary="Bulk import products (Admin)",
     *     description="Import multiple products from CSV data",
     *     tags={"Admin - Products"},
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
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="imported", type="integer"),
     *                 @OA\Property(property="failed", type="integer"),
     *                 @OA\Property(property="errors", type="array",
     *                     @OA\Items(type="object")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $products = $request->input('products', []);
        $result = $this->productService->bulkImportProducts($products);

        return response()->json([
            'success' => true,
            'message' => "Imported {$result['imported']} products, {$result['failed']} failed",
            'data' => $result,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/admin/products/low-stock",
     *     summary="Get low stock products (Admin)",
     *     description="Retrieve all products with stock below threshold",
     *     tags={"Admin - Products"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="vendor_id",
     *         in="query",
     *         description="Filter by vendor ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
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
    public function lowStock(Request $request): JsonResponse
    {
        $vendorId = $request->input('vendor_id');
        $products = $this->productService->getLowStockProducts($vendorId);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }
}
