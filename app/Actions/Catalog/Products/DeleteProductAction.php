<?php

namespace App\Actions\Catalog\Products;

use App\Exceptions\Catalog\Products\ProductHasStockException;
use App\Exceptions\Catalog\Products\ProductNotFoundException;
use App\Models\Product;

class DeleteProductAction
{
    public function __invoke(string $id): void
    {
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
