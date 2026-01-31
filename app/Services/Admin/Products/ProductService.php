<?php

namespace App\Services\Admin\Products;

use App\Exceptions\Catalog\Products\ProductHasStockException;
use App\Exceptions\Catalog\Products\ProductNotFoundException;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Store;
use App\Models\StoreProduct;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function getProducts(array $filters)
    {
        $query = Product::with(['category', 'stock', 'storeProducts']);
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        $perPage = $filters['per_page'] ?? 15;
        return $query->paginate($perPage);
    }

    public function getProduct(string $id): Product
    {
        $product = Product::with([
            'category',
            'stock.warehouse',
            'stock.storageLocation',
            'storeProducts.store',
            'attributes',
            'images'
        ])->find($id);
        
        if (!$product) {
            throw new ProductNotFoundException();
        }
        return $product;
    }

    public function createForSingleStore(array $data): Product
    {
        DB::beginTransaction();
        try {
            $product = $this->createBaseProduct($data);
            StoreProduct::create([
                'store_id' => $data['store_id'],
                'product_id' => $product->id,
                'price' => $data['price'],
                'currency' => 'MXN',
                'is_active' => true,
                'is_visible' => true,
            ]);
            if ($data['type'] === 'product' && !empty($data['initial_stock']) && $data['initial_stock'] > 0) {
                $this->createInitialStock($product->id, $data);
            }
            DB::commit();
            return $product;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function createForMultipleStores(array $data): Product
    {
        DB::beginTransaction();
        try {
            $product = $this->createBaseProduct($data);

            foreach ($data['store_ids'] as $storeId) {
                \App\Models\StoreProduct::create([
                    'store_id' => $storeId,
                    'product_id' => $product->id,
                    'price' => $data['price'],
                    'currency' => 'MXN',
                    'is_active' => true,
                    'is_visible' => true,
                ]);
            }
            DB::commit();
            return $product;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function createForAllStores(array $data): Product
    {
        DB::beginTransaction();
        try {
            $product = $this->createBaseProduct($data);
            $storeIds = Store::pluck('id');
            foreach ($storeIds as $storeId) {
                StoreProduct::create([
                    'store_id' => $storeId,
                    'product_id' => $product->id,
                    'price' => $data['price'],
                    'currency' => 'MXN',
                    'is_active' => true,
                    'is_visible' => true,
                ]);
            }
            DB::commit();
            return $product;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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

    public function update(string $id, array $data): Product
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

    /**
     * Deletes a product by ID.
     *
     * @param string $id The ID of the product to delete.
     * @throws ProductHasStockException If the product has stock and cannot be deleted.
     * @throws ProductNotFoundException If the product does not exist.
     * @return void
     */
    public function delete(string $id): void
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

    public function getStockStatus(string $id): array
    {
        /** @var Product|null $product */
        $product = Product::find($id);
        if (!$product) {
            throw new ProductNotFoundException();
        }
        $stockByWarehouse = $product->stock()
            ->with('warehouse', 'storageLocation')
            ->get()
            ->map(function($stock) {
                return [
                    'warehouse' => $stock->warehouse->name,
                    'location' => $stock->storageLocation ? $stock->storageLocation->name : 'Sin ubicación',
                    'quantity' => $stock->quantity,
                    'reserved' => $stock->reserved,
                    'available' => $stock->quantity - $stock->reserved,
                ];
            });
        return [
            'product' => $product->name,
            'total_stock' => $product->stock()->sum('quantity'),
            'by_warehouse' => $stockByWarehouse,
        ];
    }
}
