<?php

namespace App\Actions\Catalog\Products;

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
        return DB::transaction(function () use ($data) {
            $product = $this->createBaseProduct($data);
            $tenantId = $product->tenant_id;

            if (!empty($data['store_id'])) {
                $this->attachToStore($product->id, $data['store_id'], $data, $tenantId);
                if ($data['type'] === 'product' && !empty($data['initial_stock']) && $data['initial_stock'] > 0) {
                    $this->createInitialStock($product->id, $data, $tenantId);
                }
            } elseif (!empty($data['store_ids'])) {
                foreach ($data['store_ids'] as $storeId) {
                    $this->attachToStore($product->id, $storeId, $data, $tenantId);
                }
            } elseif (!empty($data['all_stores'])) {
                $storeIds = Store::pluck('id');
                foreach ($storeIds as $storeId) {
                    $this->attachToStore($product->id, $storeId, $data, $tenantId);
                }
            }
            return $product;
        });
    }

    private function createBaseProduct(array $data): Product
    {
        $tenantId = Auth::user()->tenants()->first()?->id;
        return Product::create([
            'tenant_id' => $tenantId,
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

    private function attachToStore(string $productId, string $storeId, array $data, string $tenantId): void
    {
        StoreProduct::create([
            'tenant_id' => $tenantId,
            'store_id' => $storeId,
            'product_id' => $productId,
            'price' => $data['price'],
            'currency' => 'MXN',
            'is_active' => true,
            'is_visible' => true,
        ]);
    }

    private function createInitialStock(string $productId, array $data, string $tenantId): void
    {
        Stock::create([
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'warehouse_id' => $data['warehouse_id'],
            'storage_location_id' => null,
            'quantity' => $data['initial_stock'],
            'reserved' => 0,
            'avg_cost' => $data['cost'],
        ]);
    }
}
