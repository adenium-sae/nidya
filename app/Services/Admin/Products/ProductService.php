<?php

namespace App\Services\Admin\Products;

use App\Models\Product;
use App\Models\StorageItem;
use App\Models\Store;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class ProductService {

    public function createProduct(array $data) {
        DB::beginTransaction();
        try {
            if (!empty($data['warehouse_id'])) {
                $warehouse = Warehouse::find($data['warehouse_id']);
            } else {
                $warehouse = Warehouse::where('is_active', true)
                    ->where('type', 'central')
                    ->first();
                if (!$warehouse) {
                    $warehouse = Warehouse::where('is_active', true)->first();
                }
            }
            if (!$warehouse) {
                throw new \Exception("Warehouse not found");
            }
            $product = Product::create([
                "name" => $data["name"],
                "description" => $data["description"] ?? null,
                "sku" => $data["sku"] ?? null,
                "type" => 'product',
            ]);
            StorageItem::create([
                'label' => $data['label'] ?? $product->name,
                'batch_type' => $data['batch_type'] ?? 'other',
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
            ]);
            DB::commit();
            return $product;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}