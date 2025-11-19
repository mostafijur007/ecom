<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Jobs\LowStockAlert;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * Get paginated products with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getProducts(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->getAllWithFilters($filters, $perPage);
    }

    /**
     * Get product by ID
     *
     * @param int $id
     * @return Product|null
     */
    public function getProductById(int $id): ?Product
    {
        return $this->productRepository->findWithRelations($id);
    }

    /**
     * Create a new product with validation
     *
     * @param array $data
     * @param int|null $vendorId Override vendor ID for admin
     * @return Product
     * @throws ValidationException
     */
    public function createProduct(array $data, ?int $vendorId = null): Product
    {
        $this->validateProductData($data);

        // Set vendor ID
        if ($vendorId) {
            $data['vendor_id'] = $vendorId;
        }

        $product = $this->productRepository->create($data);

        // Check low stock after creation
        if ($product->stock_quantity <= $product->low_stock_threshold) {
            LowStockAlert::dispatch($product);
        }

        return $product;
    }

    /**
     * Update product with validation
     *
     * @param int $id
     * @param array $data
     * @return Product
     * @throws ValidationException
     * @throws \Exception
     */
    public function updateProduct(int $id, array $data): Product
    {
        $product = $this->productRepository->findWithRelations($id);

        if (!$product) {
            throw new \Exception('Product not found');
        }

        $this->validateProductData($data, $product->id, true);

        $oldStock = $product->stock_quantity;
        $product = $this->productRepository->update($product, $data);

        // Check low stock if quantity changed
        if (isset($data['stock_quantity']) && $data['stock_quantity'] != $oldStock) {
            if ($product->stock_quantity <= $product->low_stock_threshold) {
                LowStockAlert::dispatch($product);
            }
        }

        return $product;
    }

    /**
     * Delete product
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function deleteProduct(int $id): bool
    {
        $product = $this->productRepository->findWithRelations($id);

        if (!$product) {
            throw new \Exception('Product not found');
        }

        return $this->productRepository->delete($product);
    }

    /**
     * Search products
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws ValidationException
     */
    public function searchProducts(string $query, int $perPage = 20): LengthAwarePaginator
    {
        $validator = Validator::make(['q' => $query], [
            'q' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $this->productRepository->search($query, $perPage);
    }

    /**
     * Bulk import products
     *
     * @param array $products
     * @param int|null $vendorId
     * @return array
     */
    public function bulkImportProducts(array $products, ?int $vendorId = null): array
    {
        // Add vendor ID to each product if provided
        if ($vendorId) {
            $products = array_map(function ($product) use ($vendorId) {
                $product['vendor_id'] = $vendorId;
                return $product;
            }, $products);
        }

        return $this->productRepository->bulkCreate($products);
    }

    /**
     * Get products by vendor
     *
     * @param int $vendorId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getVendorProducts(int $vendorId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->getByVendor($vendorId, $filters, $perPage);
    }

    /**
     * Get featured products
     *
     * @param int $limit
     * @return Collection
     */
    public function getFeaturedProducts(int $limit = 10): Collection
    {
        return $this->productRepository->getFeatured($limit);
    }

    /**
     * Get low stock products
     *
     * @param int|null $vendorId
     * @return Collection
     */
    public function getLowStockProducts(?int $vendorId = null): Collection
    {
        return $this->productRepository->getLowStock($vendorId);
    }

    /**
     * Increment product views
     *
     * @param int $id
     * @return void
     */
    public function incrementViews(int $id): void
    {
        $product = $this->productRepository->findWithRelations($id);
        if ($product) {
            $this->productRepository->incrementViews($product);
        }
    }

    /**
     * Check if user owns product (vendor check)
     *
     * @param int $productId
     * @param int $vendorId
     * @return bool
     */
    public function isOwnedByVendor(int $productId, int $vendorId): bool
    {
        $product = $this->productRepository->findWithRelations($productId);
        return $product && $product->vendor_id === $vendorId;
    }

    /**
     * Validate product data
     *
     * @param array $data
     * @param int|null $productId
     * @param bool $isUpdate
     * @return void
     * @throws ValidationException
     */
    private function validateProductData(array $data, ?int $productId = null, bool $isUpdate = false): void
    {
        $rules = [
            'category_id' => $isUpdate ? 'sometimes|exists:categories,id' : 'required|exists:categories,id',
            'vendor_id' => 'sometimes|exists:users,id',
            'name' => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            'description' => $isUpdate ? 'sometimes|string' : 'required|string',
            'short_description' => 'nullable|string|max:500',
            'sku' => $isUpdate ? 'sometimes|string|unique:products,sku,' . $productId : 'required|string|unique:products,sku',
            'price' => $isUpdate ? 'sometimes|numeric|min:0' : 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => $isUpdate ? 'sometimes|integer|min:0' : 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'track_inventory' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'attributes' => 'nullable|array',
            'dimensions' => 'nullable|array',
            'weight' => 'nullable|numeric|min:0',
            'weight_unit' => 'nullable|string|in:kg,g,lb,oz',
            'meta_data' => 'nullable|array',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
