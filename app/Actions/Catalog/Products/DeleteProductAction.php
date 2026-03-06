<?php

namespace App\Actions\Catalog\Products;

use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Exceptions\Catalog\Products\ProductHasStockException;
use App\Exceptions\Catalog\Products\ProductNotFoundException;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class DeleteProductAction
{
    public function __invoke(string $id): void
    {
        if (!Auth::user()->tokenCan("products.delete")) {
            throw new AccessDeniedException(__("messages.no_action_permission"));
        }
        /** @var Product|null $product */
        $product = Product::find($id);
        if (!$product) {
            throw new ProductNotFoundException();
        }
        $totalStock = $product->stock()->sum('quantity');
        if ($totalStock > 0) {
            throw new ProductHasStockException();
        }
        $product->delete();
    }
}
