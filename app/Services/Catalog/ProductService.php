<?php

namespace App\Services\Catalog;

use App\Actions\Catalog\Products\CreateProductAction;
use App\Actions\Catalog\Products\DeleteProductAction;
use App\Exceptions\Catalog\Products\ProductNotFoundException;
use App\Models\Product;

class ProductService
{
    public function __construct(
        protected CreateProductAction $createProductAction,
        protected DeleteProductAction $deleteProductAction,
    ) {}

    public function getProducts(array $filters)
    {
        $query = Product::with(["category", "storeProducts", "stock" => function ($q) use ($filters) {
            if (!empty($filters["warehouse_id"])) {
                $q->where("warehouse_id", $filters["warehouse_id"]);
            }
        }])->withSum(["stock as total_stock" => function ($q) use ($filters) {
                if (!empty($filters["warehouse_id"])) {
                    $q->where("warehouse_id", $filters["warehouse_id"]);
                }
                if (!empty($filters["storage_location_id"])) {
                    $q->where("storage_location_id", $filters["storage_location_id"]);
                }
            }
        ], "quantity");
        if (!empty($filters["search"])) {
            $search = $filters["search"];
            $query->where(function ($q) use ($search) {
                $q->where("name", "like", "%{$search}%")
                    ->orWhere("sku", "like", "%{$search}%")
                    ->orWhere("barcode", "like", "%{$search}%");
            });
        }
        if (!empty($filters["category_id"])) {
            $query->where("category_id", $filters["category_id"]);
        }
        if (!empty($filters["type"])) {
            $query->where("type", $filters["type"]);
        }
        if (isset($filters["is_active"])) {
            $query->where("is_active", $filters["is_active"]);
        }
        $perPage = $filters["per_page"] ?? 15;
        return $query->paginate($perPage);
    }

    public function getProduct(string $id): Product
    {
        $product = Product::with([
            "category",
            "stock.warehouse",
            "stock.storageLocation",
            "storeProducts.store",
            "attributes",
            "images",
        ])->find($id);

        if (!$product) {
            throw new ProductNotFoundException();
        }
        return $product;
    }

    public function create(array $data): Product
    {
        return ($this->createProductAction)($data);
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
        return $product->fresh("category");
    }

    public function delete(string $id): void
    {
        ($this->deleteProductAction)($id);
    }

    public function getStockStatus(string $id): array
    {
        /** @var Product|null $product */
        $product = Product::find($id);
        if (!$product) {
            throw new ProductNotFoundException();
        }
        $stockByWarehouse = $product
            ->stock()
            ->with("warehouse", "storageLocation")
            ->get()
            ->map(function ($stock) {
                return [
                    "warehouse" => $stock->warehouse->name,
                    "location" => $stock->storageLocation
                        ? $stock->storageLocation->name
                        : "Sin ubicación",
                    "quantity" => $stock->quantity,
                    "reserved" => $stock->reserved,
                    "available" => $stock->quantity - $stock->reserved,
                ];
            });
        return [
            "product" => $product->name,
            "total_stock" => $product->stock()->sum("quantity"),
            "by_warehouse" => $stockByWarehouse,
        ];
    }
}
