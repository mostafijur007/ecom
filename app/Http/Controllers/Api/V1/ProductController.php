<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="Product management endpoints"
 * )
 */
class ProductController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *     path="/api/v1/products",
     *     summary="Get list of products",
     *     description="Retrieve paginated list of products with optional filtering and search",
     *     operationId="getProducts",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Full-text search on product name and description",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
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
     *         name="is_active",
     *         in="query",
     *         description="Filter by active status",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="is_featured",
     *         in="query",
     *         description="Filter by featured status",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         description="Minimum price filter",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         description="Maximum price filter",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="in_stock",
     *         in="query",
     *         description="Filter products in stock only",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort by field (default: created_at)",
     *         required=false,
     *         @OA\Schema(type="string", default="created_at")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Sort order (asc or desc, default: desc)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page (default: 15)",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Products retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Products retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Product")),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     *
     * Display a listing of products with filtering and search
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'vendor', 'variants']);

        // Search by keyword (full-text search)
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereRaw('MATCH(name, description) AGAINST(? IN BOOLEAN MODE)', [$search]);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by vendor
        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by featured
        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Filter by stock status
        if ($request->has('in_stock') && $request->boolean('in_stock')) {
            $query->where('stock_quantity', '>', 0);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 15);
        $products = $query->paginate($perPage);

        return $this->successResponse($products, 'Products retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/products",
     *     summary="Create a new product",
     *     description="Create a new product (Vendor/Admin only)",
     *     operationId="createProduct",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"category_id","name","description","sku","price","stock_quantity"},
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Wireless Mouse"),
     *             @OA\Property(property="description", type="string", example="Ergonomic wireless mouse with USB receiver"),
     *             @OA\Property(property="short_description", type="string", example="Comfortable wireless mouse"),
     *             @OA\Property(property="sku", type="string", example="MOUSE-001"),
     *             @OA\Property(property="price", type="number", format="float", example=29.99),
     *             @OA\Property(property="sale_price", type="number", format="float", example=24.99),
     *             @OA\Property(property="cost_price", type="number", format="float", example=15.00),
     *             @OA\Property(property="stock_quantity", type="integer", example=100),
     *             @OA\Property(property="low_stock_threshold", type="integer", example=10),
     *             @OA\Property(property="track_inventory", type="boolean", example=true),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="is_featured", type="boolean", example=false),
     *             @OA\Property(property="images", type="array", @OA\Items(type="string", example="https://example.com/image.jpg")),
     *             @OA\Property(property="attributes", type="object", example={"color": "Black", "connectivity": "2.4GHz"}),
     *             @OA\Property(property="dimensions", type="object", example={"length": 10, "width": 6, "height": 4}),
     *             @OA\Property(property="weight", type="number", format="float", example=0.15),
     *             @OA\Property(property="meta_data", type="object")
     *         )
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
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Not a vendor or admin")
     * )
     *
     * Store a newly created product
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'sku' => 'required|string|unique:products,sku',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'track_inventory' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'string|url',
            'attributes' => 'nullable|array',
            'dimensions' => 'nullable|array',
            'weight' => 'nullable|numeric|min:0',
            'meta_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $data = $validator->validated();
            $data['vendor_id'] = auth()->user()->isVendor() ? auth()->id() : $request->vendor_id;
            $data['slug'] = Str::slug($request->name);

            $product = Product::create($data);

            return $this->createdResponse($product->load(['category', 'vendor']), 'Product created successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create product: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/{id}",
     *     summary="Get product details",
     *     description="Retrieve detailed information about a specific product",
     *     operationId="getProduct",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
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
     *             @OA\Property(property="message", type="string", example="Product retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     *
     * Display the specified product
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::with(['category', 'vendor', 'variants'])->find($id);

        if (!$product) {
            return $this->notFoundResponse('Product not found');
        }

        // Increment views
        $product->incrementViews();

        return $this->successResponse($product, 'Product retrieved successfully');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/products/{id}",
     *     summary="Update product",
     *     description="Update an existing product (Owner/Admin only)",
     *     operationId="updateProduct",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="category_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="short_description", type="string"),
     *             @OA\Property(property="sku", type="string"),
     *             @OA\Property(property="price", type="number", format="float"),
     *             @OA\Property(property="sale_price", type="number", format="float"),
     *             @OA\Property(property="stock_quantity", type="integer"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="is_featured", type="boolean")
     *         )
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
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden - Can only update own products"),
     *     @OA\Response(response=404, description="Product not found")
     * )
     *
     * Update the specified product
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->notFoundResponse('Product not found');
        }

        // Check ownership for vendors
        if (auth()->user()->isVendor() && $product->vendor_id !== auth()->id()) {
            return $this->forbiddenResponse('You can only update your own products');
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'short_description' => 'nullable|string|max:500',
            'sku' => 'sometimes|string|unique:products,sku,' . $id,
            'price' => 'sometimes|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'sometimes|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'track_inventory' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'string|url',
            'attributes' => 'nullable|array',
            'dimensions' => 'nullable|array',
            'weight' => 'nullable|numeric|min:0',
            'meta_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $data = $validator->validated();
            
            if (isset($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $product->update($data);

            return $this->successResponse($product->load(['category', 'vendor']), 'Product updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update product: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/products/{id}",
     *     summary="Delete product",
     *     description="Soft delete a product (Owner/Admin only)",
     *     operationId="deleteProduct",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
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
     *             @OA\Property(property="message", type="string", example="Product deleted successfully"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - Can only delete own products"),
     *     @OA\Response(response=404, description="Product not found")
     * )
     *
     * Remove the specified product
     */
    public function destroy(string $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->notFoundResponse('Product not found');
        }

        // Check ownership for vendors
        if (auth()->user()->isVendor() && $product->vendor_id !== auth()->id()) {
            return $this->forbiddenResponse('You can only delete your own products');
        }

        try {
            $product->delete();
            return $this->successResponse(null, 'Product deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete product: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/products/bulk-import",
     *     summary="Bulk import products from CSV",
     *     description="Import multiple products from a CSV file (Vendor/Admin only)",
     *     operationId="bulkImportProducts",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="file",
     *                     description="CSV file with products (headers: name, sku, price, category_id, description, stock_quantity, etc.)",
     *                     type="string",
     *                     format="binary"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Products imported successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="10 products imported successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="imported", type="integer", example=10),
     *                 @OA\Property(property="errors", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden - Not a vendor or admin")
     * )
     *
     * Bulk import products from CSV
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $file = $request->file('file');
            $csvData = array_map('str_getcsv', file($file->getRealPath()));
            $headers = array_shift($csvData);

            $imported = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($csvData as $index => $row) {
                try {
                    $data = array_combine($headers, $row);
                    
                    // Basic validation
                    if (empty($data['name']) || empty($data['sku']) || empty($data['price'])) {
                        $errors[] = "Row " . ($index + 2) . ": Missing required fields";
                        continue;
                    }

                    $product = Product::create([
                        'vendor_id' => auth()->user()->isVendor() ? auth()->id() : ($data['vendor_id'] ?? null),
                        'category_id' => $data['category_id'] ?? null,
                        'name' => $data['name'],
                        'slug' => Str::slug($data['name']),
                        'sku' => $data['sku'],
                        'description' => $data['description'] ?? '',
                        'short_description' => $data['short_description'] ?? null,
                        'price' => $data['price'],
                        'sale_price' => $data['sale_price'] ?? null,
                        'cost_price' => $data['cost_price'] ?? null,
                        'stock_quantity' => $data['stock_quantity'] ?? 0,
                        'low_stock_threshold' => $data['low_stock_threshold'] ?? 10,
                        'track_inventory' => isset($data['track_inventory']) ? (bool)$data['track_inventory'] : true,
                        'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true,
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            return $this->successResponse([
                'imported' => $imported,
                'errors' => $errors,
            ], "$imported products imported successfully");

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to import products: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/search",
     *     summary="Search products",
     *     description="Full-text search on product name and description",
     *     operationId="searchProducts",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Search query (minimum 2 characters)",
     *         required=true,
     *         @OA\Schema(type="string", example="laptop")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Search results retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Product")),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     *
     * Search products with full-text search
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $query = $request->q;

        $products = Product::with(['category', 'vendor'])
            ->whereRaw('MATCH(name, description) AGAINST(? IN BOOLEAN MODE)', [$query])
            ->active()
            ->paginate(20);

        return $this->successResponse($products, 'Search results retrieved successfully');
    }
}
