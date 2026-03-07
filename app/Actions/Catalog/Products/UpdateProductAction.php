<?php

namespace App\Actions\Catalog\Products;

use App\Exceptions\Catalog\Products\ProductNotFoundException;
use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class UpdateProductAction
{
    public function __invoke(string $id, array $data): Product
    {
        $product = Product::findOrFail($id);
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check if user has permission in any of the product's stores
        $productStoreIds = $product->stores()->pluck('stores.id')->toArray();
        $hasPermission = false;
        foreach ($productStoreIds as $storeId) {
            if ($user->hasPermissionInStore('products.edit', $storeId)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            throw new AccessDeniedException();
        }

        $product->fill($data);
        $product->save();
        return $product->fresh('category');
    }
}
