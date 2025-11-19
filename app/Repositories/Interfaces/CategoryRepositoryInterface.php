<?php

namespace App\Repositories\Interfaces;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    /**
     * Get all categories
     *
     * @param bool $withChildren
     * @return Collection
     */
    public function getAll(bool $withChildren = false): Collection;

    /**
     * Find category by ID
     *
     * @param int $id
     * @return Category|null
     */
    public function findById(int $id): ?Category;

    /**
     * Find category by slug
     *
     * @param string $slug
     * @return Category|null
     */
    public function findBySlug(string $slug): ?Category;

    /**
     * Get parent categories only
     *
     * @return Collection
     */
    public function getParents(): Collection;

    /**
     * Get children categories by parent ID
     *
     * @param int $parentId
     * @return Collection
     */
    public function getChildren(int $parentId): Collection;

    /**
     * Get active categories
     *
     * @return Collection
     */
    public function getActive(): Collection;
}
