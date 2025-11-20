<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private Product $model
    ) {
    }

    public function getAllWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['category', 'vendor', 'variants']);

        // Search by keyword (use LIKE for SQLite compatibility)
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            if (config('database.default') === 'sqlite') {
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'LIKE', "%{$searchTerm}%");
                });
            } else {
                $query->whereRaw('MATCH(name, description) AGAINST(? IN BOOLEAN MODE)', [$searchTerm]);
            }
        }

        // Filter by category
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filter by vendor
        if (!empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        // Filter by active status
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Filter by featured
        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        // Filter by price range
        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }
        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Filter by stock status
        if (!empty($filters['in_stock'])) {
            $query->where('stock_quantity', '>', 0);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    public function findWithRelations(int $id, array $relations = []): ?Product
    {
        $defaultRelations = ['category', 'vendor', 'variants'];
        $relations = !empty($relations) ? $relations : $defaultRelations;
        
        return $this->model->with($relations)->find($id);
    }

    public function create(array $data): Product
    {
        // Generate slug if not provided
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return $this->model->create($data);
    }

    public function update(Product $product, array $data): Product
    {
        // Update slug if name changed
        if (!empty($data['name']) && $data['name'] !== $product->name) {
            $data['slug'] = Str::slug($data['name']);
        }

        $product->update($data);
        return $product->fresh();
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    public function search(string $query, int $perPage = 20): LengthAwarePaginator
    {
        $builder = $this->model->with(['category', 'vendor']);
        
        if (config('database.default') === 'sqlite') {
            $builder->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            });
        } else {
            $builder->whereRaw('MATCH(name, description) AGAINST(? IN BOOLEAN MODE)', [$query]);
        }
        
        return $builder->where('is_active', true)->paginate($perPage);
    }

    public function getByVendor(int $vendorId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['vendor_id'] = $vendorId;
        return $this->getAllWithFilters($filters, $perPage);
    }

    public function bulkCreate(array $products): array
    {
        $imported = 0;
        $errors = [];

        foreach ($products as $index => $productData) {
            try {
                // Check if SKU already exists
                if ($this->skuExists($productData['sku'])) {
                    $errors[] = "Row " . ($index + 1) . ": SKU {$productData['sku']} already exists";
                    continue;
                }

                $this->create($productData);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
            }
        }

        return [
            'imported' => $imported,
            'errors' => $errors,
        ];
    }

    public function skuExists(string $sku, ?int $excludeId = null): bool
    {
        $query = $this->model->where('sku', $sku);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function getFeatured(int $limit = 10): Collection
    {
        return $this->model->with(['category', 'vendor'])
            ->where('is_featured', true)
            ->where('is_active', true)
            ->limit($limit)
            ->get();
    }

    public function getLowStock(?int $vendorId = null): Collection
    {
        $query = $this->model->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('track_inventory', true)
            ->where('is_active', true);

        if ($vendorId) {
            $query->where('vendor_id', $vendorId);
        }

        return $query->get();
    }

    public function incrementViews(Product $product): void
    {
        $product->increment('views_count');
    }
}
