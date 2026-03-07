<?php

namespace App\Actions\Catalog\Categories;

use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Exceptions\Catalog\Categories\CategoryNotFoundException;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;

class DeleteCategoryAction
{
    public function __invoke(string $id): void
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
        $category->delete();
    }
}
