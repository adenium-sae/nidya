<?php

namespace App\Services\Products;

use App\Exceptions\Catalog\Products\ProductHasStockException;
use App\Models\Product;

class ProductService
{
    public function list(array $filters, array $sort, int $perPage)
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
        if (!empty($filters['only_active'])) {
            $query->where('is_active', true);
        }
        if (!empty($filters['low_stock'])) {
            $query->whereRaw('(SELECT SUM(quantity - reserved) FROM stock WHERE product_id = products.id) <= products.min_stock');
        }
        $query->orderBy($sort['by'] ?? 'name', $sort['order'] ?? 'asc');
        return $query->paginate($perPage);
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        foreach ($data as $key => $value) {
            $product->$key = $value;
        }
        $product->save();
        
        return $product;
    }

    public function delete(Product $product): void
    {
        $totalStock = $product->stock()->sum('quantity');
        if ($totalStock > 0) {
            throw new ProductHasStockException();
        }
        $product->delete();
    }

    public function getStockStatus(Product $product): array
    {
        $stockByWarehouse = $product->stock()
            ->with('warehouse', 'storageLocation')
            ->get()
            ->map(function($stock) {
                return [
                    'warehouse' => $stock->warehouse->name,
                    'location' => $stock->storageLocation ? $stock->storageLocation->name : 'Sin ubicación',
                    'quantity' => $stock->quantity,
                    'reserved' => $stock->reserved,
                    'available' => $stock->getAvailableQuantity(),
                ];
            });
        return [
            'product' => $product->name,
            'total_stock' => $product->total_stock,
            'available_stock' => $product->available_stock,
            'needs_restock' => $product->needsRestock(),
            'by_warehouse' => $stockByWarehouse,
        ];
    }
}
