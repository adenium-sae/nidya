<?php

namespace App\Actions\Catalog\Products;

use App\Exceptions\Catalog\Products\ProductNotFoundException;
use App\Models\Product;

class UpdateProductAction
{
    public function __invoke(string $id, array $data): Product
    {
        /** @var Product|null $product */
        $product = Product::find($id);
        if (!$product) {
            throw new ProductNotFoundException();
        }
        $product->fill($data);
        $product->save();
        return $product->fresh('category');
    }
}
