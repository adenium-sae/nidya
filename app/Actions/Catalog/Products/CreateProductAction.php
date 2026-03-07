<?php

namespace App\Actions\Catalog\Products;

use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Store;
use App\Models\StoreProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateProductAction
{
    public function __invoke(array $data): Product
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $targetStoreIds = [];
        if (!empty($data['store_id'])) {
            $targetStoreIds[] = $data['store_id'];
        } elseif (!empty($data['store_ids'])) {
            $targetStoreIds = $data['store_ids'];
        } elseif (!empty($data['all_stores'])) {
            $targetStoreIds = Store::pluck('id')->toArray();
        }

        foreach ($targetStoreIds as $storeId) {
            if (!$user->hasPermissionInStore('products.create', $storeId)) {
                throw new AccessDeniedException();
            }
        }

        return DB::transaction(function () use ($data, $targetStoreIds) {
            $product = $this->createBaseProduct($data);

            foreach ($targetStoreIds as $storeId) {
                $this->attachToStore($product->id, $storeId, $data);
            }

            if ($data['type'] === 'product' && !empty($data['initial_stock']) && $data['initial_stock'] > 0 && !empty($data['warehouse_id'])) {
                $this->createInitialStock($product->id, $data);
            }

            return $product;
        });
    }

    private function createBaseProduct(array $data): Product
    {
        return Product::create([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'sku' => $data['sku'],
            'barcode' => $data['barcode'] ?? null,
            'type' => $data['type'],
            'cost' => $data['cost'],
            'image_url' => $data['image_url'] ?? null,
            'track_inventory' => $data['type'] === 'product' ? ($data['track_inventory'] ?? true) : false,
            'min_stock' => $data['min_stock'] ?? 0,
            'max_stock' => $data['max_stock'] ?? 0,
            'is_active' => true,
        ]);
    }

    private function attachToStore(string $productId, string $storeId, array $data): void
    {
        StoreProduct::create([
            'store_id' => $storeId,
            'product_id' => $productId,
            'price' => $data['price'],
            'currency' => 'MXN',
            'is_active' => true,
            'is_visible' => true,
        ]);
    }

    private function createInitialStock(string $productId, array $data): void
    {
        Stock::create([
            'product_id' => $productId,
            'warehouse_id' => $data['warehouse_id'],
            'storage_location_id' => null,
            'quantity' => $data['initial_stock'],
            'reserved' => 0,
            'avg_cost' => $data['cost'],
        ]);
    }
}
