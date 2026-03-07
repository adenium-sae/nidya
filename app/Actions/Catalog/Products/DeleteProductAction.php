<?php

namespace App\Actions\Catalog\Products;

use App\Exceptions\Catalog\Products\ProductHasStockException;
use App\Exceptions\Catalog\Products\ProductNotFoundException;
use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class DeleteProductAction
{
    public function __invoke(string $id): void
    {
        $product = Product::findOrFail($id);
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check if user has permission in any of the product's stores
        $productStoreIds = $product->stores()->pluck('stores.id')->toArray();
        $hasPermission = false;
        foreach ($productStoreIds as $storeId) {
            if ($user->hasPermissionInStore('products.delete', $storeId)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            throw new AccessDeniedException();
        }

        $totalStock = $product->stock()->sum('quantity');
        if ($totalStock > 0) {
            throw new ProductHasStockException();
        }
        $product->delete();
    }
}
