<?php

namespace App\Actions\Catalog\Categories;

use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Exceptions\Catalog\Categories\CategoryNotFoundException;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UpdateCategoryAction
{
    public function __invoke(string $id, array $data): Category
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Authorization check: User must have catalog.manage in at least one store
        $storeIds = $user->getAccessibleStoreIds('catalog.manage');
        if (empty($storeIds)) {
            throw new AccessDeniedException();
        }

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
