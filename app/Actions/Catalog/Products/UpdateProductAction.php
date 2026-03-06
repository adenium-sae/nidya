<?php

namespace App\Actions\Catalog\Products;

use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Exceptions\Catalog\Products\ProductNotFoundException;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class UpdateProductAction
{
    public function __invoke(string $id, array $data): Product
    {
        if (!Auth::user()->tokenCan("products.update")) {
            throw new AccessDeniedException(__("messages.no_action_permission"));
        }
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
