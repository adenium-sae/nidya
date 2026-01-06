<?php

namespace App\Services\Admin\Products;

use App\Exceptions\Warehouses\WarehouseNotFoundException;
use App\Models\Product;
use App\Models\StorageItem;
use App\Models\StockItem;
use App\Models\Store;
use App\Models\Warehouse;
use App\Models\StoreProduct;
use Illuminate\Support\Facades\DB;

class ProductService {

    public function getProducts(array $filters = []) {
        $query = Product::query();
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('sku', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['store_id'])) {
            $query->whereHas('storeProducts', function($q) use ($filters) {
                $q->where('store_id', $filters['store_id']);
            });
        }
        if (!empty($filters['warehouse_id'])) {
            $query->whereHas('storageItems', function($q) use ($filters) {
                $q->where('warehouse_id', $filters['warehouse_id']);
            });
        }
        if (isset($filters['is_active'])) {
            $query->whereHas('storeProducts', function($q) use ($filters) {
                $q->where('is_active', $filters['is_active']);
            });
        }
        if (isset($filters['min_stock'])) {
            $query->whereHas('stockItems', function($q) use ($filters) {
                $q->where('stock', '>=', $filters['min_stock']);
            });
        }
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);
        $perPage = $filters['per_page'] ?? 15;
        $paginated = $query->paginate($perPage);
        $requestedColumns = $this->parseColumns($filters['columns'] ?? null);
        $paginated->getCollection()->transform(function ($product) use ($requestedColumns) {
            return $this->normalizeProductForTable($product, $requestedColumns);
        });
        return $paginated;
    }

    private function parseColumns(?string $columns): array {
        if (empty($columns)) {
            return [];
        }
        $allowed = [
            'id', 'name', 'sku', 'type', 'description',
            'total_stock', 'stores_count', 'stores',
            'warehouses_count', 'warehouses',
            'min_price', 'max_price', 'avg_price', 'currency',
            'is_active', 'created_at', 'updated_at'
        ];
        $requested = array_map('trim', explode(',', $columns));
        return array_values(array_intersect($requested, $allowed));
    }

    public function getProduct(string $id) {
        return Product::with(['storeProducts.store', 'storageItems.warehouse', 'stockItems'])
            ->findOrFail($id);
    }

    private function normalizeProductForTable($product, array $requestedColumns = []): array {
        $product->loadMissing(['storeProducts.store', 'storageItems.warehouse', 'stockItems']);
        $storeProducts = $product->storeProducts;
        $stockItems = $product->stockItems;
        $storageItems = $product->storageItems;
        $allData = [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'type' => $product->type,
            'description' => $product->description,
            'total_stock' => $stockItems->sum('stock'),
            'stores_count' => $storeProducts->count(),
            'stores' => $storeProducts->pluck('store.name')->toArray(),
            'warehouses_count' => $storageItems->unique('warehouse_id')->count(),
            'warehouses' => $storageItems->pluck('warehouse.name')->unique()->values()->toArray(),
            'min_price' => $storeProducts->min('price'),
            'max_price' => $storeProducts->max('price'),
            'avg_price' => round($storeProducts->avg('price'), 2),
            'currency' => $storeProducts->first()->currency ?? 'USD',
            'is_active' => $storeProducts->where('is_active', true)->isNotEmpty(),
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
        ];
        if (empty($requestedColumns)) {
            return $allData;
        }
        $filtered = ['id' => $allData['id']];
        foreach ($requestedColumns as $col) {
            if (isset($allData[$col])) {
                $filtered[$col] = $allData[$col];
            }
        }
        return $filtered;
    }

    public function createProduct(array $data) {
        DB::beginTransaction();
        try {
            $product = Product::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'sku' => $data['sku'] ?? null,
                'type' => 'product',
            ]);
            $scope = $data['stores_scope'] ?? 'single';
            if ($scope === 'all') {
                $stores = Store::where('is_active', true)->get();
            } else {
                $storeIds = $data['store_ids'] ?? [];
                $stores = Store::whereIn('id', (array) $storeIds)->get();
            }
            $globalWarehouse = null;
            if (!empty($data['warehouse_id'])) {
                $globalWarehouse = Warehouse::find($data['warehouse_id']);
            } else {
                $globalWarehouse = Warehouse::where('is_active', true)
                    ->where('type', 'central')
                    ->first();
                if (!$globalWarehouse) {
                    $globalWarehouse = Warehouse::where('is_active', true)->first();
                }
            }
            $globalStock = $data['stock'] ?? 0;
            $storeStocks = $data['store_stocks'] ?? [];
            if ($stores->isNotEmpty()) {
                foreach ($stores as $store) {
                    if (!empty($data['warehouse_id']) && $globalWarehouse) {
                        $warehouse = $globalWarehouse;
                    } else {
                        $warehouse = Warehouse::where('is_active', true)
                            ->where('store_id', $store->id)
                            ->where('type', 'central')
                            ->first();
                        if (!$warehouse) {
                            $warehouse = Warehouse::where('is_active', true)
                                ->where('store_id', $store->id)
                                ->first();
                        }
                        if (!$warehouse) {
                            $warehouse = $globalWarehouse;
                        }
                    }
                    if (!$warehouse) {
                        throw new WarehouseNotFoundException();
                    }
                    $storageItem = StorageItem::create([
                        'label' => $data['label'] ?? $product->name,
                        'batch_type' => $data['batch_type'] ?? 'other',
                        'warehouse_id' => $warehouse->id,
                        'store_id' => $store->id,
                        'product_id' => $product->id,
                    ]);
                    $stockValue = $globalStock;
                    if (is_array($storeStocks) && array_key_exists($store->id, $storeStocks)) {
                        $stockValue = (int) $storeStocks[$store->id];
                    }
                    StockItem::create([
                        'product_id' => $product->id,
                        'storage_item_id' => $storageItem->id,
                        'store_id' => $store->id,
                        'stock' => $stockValue,
                    ]);
                    StoreProduct::updateOrCreate(
                        ['store_id' => $store->id, 'product_id' => $product->id],
                        ['price' => $data['price'] ?? 0, 'currency' => $data['currency'] ?? 'USD', 'is_active' => true]
                    );
                }
            } else {
                if (!$globalWarehouse) {
                    throw new WarehouseNotFoundException();
                }
                if (empty($globalWarehouse->store_id)) {
                    throw new \Exception("Global warehouse must have a store_id assigned");
                }
                $storageItem = StorageItem::create([
                    'label' => $data['label'] ?? $product->name,
                    'batch_type' => $data['batch_type'] ?? 'other',
                    'warehouse_id' => $globalWarehouse->id,
                    'store_id' => $globalWarehouse->store_id,
                    'product_id' => $product->id,
                ]);
                StockItem::create([
                    'product_id' => $product->id,
                    'storage_item_id' => $storageItem->id,
                    'store_id' => $globalWarehouse->store_id,
                    'stock' => $globalStock,
                ]);
                if (!empty($globalWarehouse->store_id)) {
                    StoreProduct::updateOrCreate(
                        ['store_id' => $globalWarehouse->store_id, 'product_id' => $product->id],
                        ['price' => $data['price'] ?? 0, 'currency' => $data['currency'] ?? 'USD', 'is_active' => true]
                    );
                }
            }
            DB::commit();
            return $product;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}