<?php

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(
        private Category $model
    ) {
    }

    public function getAll(bool $withChildren = false): Collection
    {
        $query = $this->model->query();

        if ($withChildren) {
            $query->with('children');
        }

        return $query->orderBy('sort_order')->get();
    }

    public function findById(int $id): ?Category
    {
        return $this->model->find($id);
    }

    public function findBySlug(string $slug): ?Category
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function getParents(): Collection
    {
        return $this->model->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();
    }

    public function getChildren(int $parentId): Collection
    {
        return $this->model->where('parent_id', $parentId)
            ->orderBy('sort_order')
            ->get();
    }

    public function getActive(): Collection
    {
        return $this->model->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
