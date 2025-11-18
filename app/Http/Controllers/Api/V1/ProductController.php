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

class ProductController extends Controller
{
    use ApiResponse;

    /**
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
