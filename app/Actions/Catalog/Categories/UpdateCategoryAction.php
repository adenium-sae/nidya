<?php

namespace App\Actions\Catalog\Categories;

use App\Exceptions\Catalog\Categories\CategoryNotFoundException;
use App\Models\Category;
use Illuminate\Support\Str;

class UpdateCategoryAction
{
    public function __invoke(string $id, array $data): Category
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
}
