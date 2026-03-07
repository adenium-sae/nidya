<?php

namespace App\Actions\Catalog\Categories;

use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateCategoryAction
{
    public function __invoke(array $data): Category
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Authorization check: User must have catalog.manage in at least one store
        $storeIds = $user->getAccessibleStoreIds('catalog.manage');
        if (empty($storeIds)) {
            throw new AccessDeniedException();
        }

        $data['slug'] = Str::slug($data['name']);
        return Category::create($data);
    }
}
