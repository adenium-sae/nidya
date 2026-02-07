<?php

namespace App\Services\Catalog;

use App\Exceptions\Catalog\Categories\CategoryNotFoundException;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoryService
{
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

    public function create(array $data, string $tenantId): Category
    {
        $data['slug'] = Str::slug($data['name']);
        $data['tenant_id'] = $tenantId;
        
        return Category::create($data);
    }

    public function update(string $id, array $data): Category
    {
        $category = Category::find($id);
        if (!$category) {
            throw new CategoryNotFoundException();
        }

        if (isset($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);
        return $category->fresh();
    }

    public function delete(string $id): void
    {
        $category = Category::find($id);
        if (!$category) {
            throw new CategoryNotFoundException();
        }
        $category->delete();
    }
}
