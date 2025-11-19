<?php

namespace App\Repositories\Interfaces;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    /**
     * Get all products with optional filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Find product by ID with relationships
     *
     * @param int $id
     * @param array $relations
     * @return Product|null
     */
    public function findWithRelations(int $id, array $relations = []): ?Product;

    /**
     * Create a new product
     *
     * @param array $data
     * @return Product
     */
    public function create(array $data): Product;

    /**
     * Update a product
     *
     * @param Product $product
     * @param array $data
     * @return Product
     */
    public function update(Product $product, array $data): Product;

    /**
     * Delete a product (soft delete)
     *
     * @param Product $product
     * @return bool
     */
    public function delete(Product $product): bool;

    /**
     * Search products using full-text search
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(string $query, int $perPage = 20): LengthAwarePaginator;

    /**
     * Get products by vendor ID
     *
     * @param int $vendorId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByVendor(int $vendorId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Bulk create products
     *
     * @param array $products
     * @return array ['imported' => int, 'errors' => array]
     */
    public function bulkCreate(array $products): array;

    /**
     * Check if SKU exists
     *
     * @param string $sku
     * @param int|null $excludeId
     * @return bool
     */
    public function skuExists(string $sku, ?int $excludeId = null): bool;

    /**
     * Get featured products
     *
     * @param int $limit
     * @return Collection
     */
    public function getFeatured(int $limit = 10): Collection;

    /**
     * Get low stock products
     *
     * @param int|null $vendorId
     * @return Collection
     */
    public function getLowStock(?int $vendorId = null): Collection;

    /**
     * Increment product views
     *
     * @param Product $product
     * @return void
     */
    public function incrementViews(Product $product): void;
}
