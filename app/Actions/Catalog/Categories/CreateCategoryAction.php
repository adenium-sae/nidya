<?php

namespace App\Actions\Catalog\Categories;

use App\Exceptions\Catalog\Categories\CategoryNotFoundException;
use App\Models\Category;
use Illuminate\Support\Str;

class CreateCategoryAction
{
    public function __invoke(array $data): Category
    {
        $data['slug'] = Str::slug($data['name']);
        return Category::create($data);
    }
}
