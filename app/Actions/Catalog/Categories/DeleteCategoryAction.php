<?php

namespace App\Actions\Catalog\Categories;

use App\Exceptions\Catalog\Categories\CategoryNotFoundException;
use App\Models\Category;

class DeleteCategoryAction
{
    public function __invoke(string $id): void
    {
        $category = Category::find($id);
        if (!$category) {
            throw new CategoryNotFoundException();
        }
        $category->delete();
    }
}
