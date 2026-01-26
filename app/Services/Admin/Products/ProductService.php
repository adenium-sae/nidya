<?php

namespace App\Services\Admin\Products;

use App\Exceptions\Products\ProductHasStockException;
use App\Exceptions\Products\ProductNotFoundException;
use App\Models\Product;

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

    public function createProduct(array $data): Product
    {
        return Product::create($data);
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
