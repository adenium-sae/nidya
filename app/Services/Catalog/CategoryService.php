<?php

namespace App\Services\Catalog;

use App\Actions\Catalog\Categories\CreateCategoryAction;
use App\Actions\Catalog\Categories\DeleteCategoryAction;
use App\Actions\Catalog\Categories\UpdateCategoryAction;
use App\Exceptions\Catalog\Categories\CategoryNotFoundException;
use App\Models\Category;

class CategoryService
{
    public function __construct(
        protected CreateCategoryAction $createCategoryAction,
        protected UpdateCategoryAction $updateCategoryAction,
        protected DeleteCategoryAction $deleteCategoryAction,
    ) {}

    // --- Queries ---

    public function list(array $filters)
    {
        $query = Category::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }

        return $query->latest()->get();
    }

    public function getById(string $id): Category
    {
        $category = Category::find($id);
        if (!$category) {
            throw new CategoryNotFoundException();
        }
        return $category;
    }

    // --- Mutations (delegated to Actions) ---

    public function create(array $data): Category
    {
        return ($this->createCategoryAction)($data);
    }

    public function update(string $id, array $data): Category
    {
        return ($this->updateCategoryAction)($id, $data);
    }

    public function delete(string $id): void
    {
        ($this->deleteCategoryAction)($id);
    }
}
