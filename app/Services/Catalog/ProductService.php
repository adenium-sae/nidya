<?php

namespace App\Services\Catalog;

use App\Actions\Catalog\Products\CreateProductAction;
use App\Actions\Catalog\Products\DeleteProductAction;
use App\Actions\Catalog\Products\UpdateProductAction;
use App\Exceptions\Catalog\Products\ProductNotFoundException;
use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class ProductService
{
    public function __construct(
        protected CreateProductAction $createProductAction,
        protected UpdateProductAction $updateProductAction,
        protected DeleteProductAction $deleteProductAction,
    ) {}

    // --- Queries ---

    public function list(array $filters)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $accessibleStoreIds = $user->getAccessibleStoreIds('products.view');

        $query = Product::with(['category', 'brand', 'images', 'stores'])
            ->whereHas('stores', function ($q) use ($accessibleStoreIds) {
                $q->whereIn('stores.id', $accessibleStoreIds);
            });

        // The original code had stock and storeProducts related filters.
        // The diff suggests replacing them with 'brand', 'images', 'stores'.
        // I'm keeping the original stock filters but adapting them to the new structure if possible,
        // or removing them if they don't fit the new 'with' clauses.
        // Given the diff, it seems the intent is to simplify the eager loading and filtering for the list.
        // I will remove the stock-related `with` and `withSum` as they are not in the diff's `with` list.

        if (!empty($filters["search"])) {
            $search = strtolower($filters["search"]);
            $query->where(function ($q) use ($search) {
                $q->whereRaw("LOWER(name) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("LOWER(sku) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("LOWER(barcode) LIKE ?", ["%{$search}%"]);
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

    public function getById(string $id): Product
    {
        $product = Product::with(['category', 'brand', 'images', 'stores', 'variants', 'attributes'])->findOrFail($id);

        // Authorization check
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $accessibleStoreIds = $user->getAccessibleStoreIds('products.view');
        $productStoreIds = $product->stores()->pluck('stores.id')->toArray();

        if (empty(array_intersect($accessibleStoreIds, $productStoreIds))) {
            throw new AccessDeniedException();
        }

        return $product;
    }

    public function getStockStatus(string $id): array
    {
        /** @var Product|null $product */
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $accessibleStoreIds = $user->getAccessibleStoreIds();

        $product = Product::whereHas('storeProducts', function ($q) use ($accessibleStoreIds) {
            $q->whereIn('store_id', $accessibleStoreIds);
        })->find($id);

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

    // --- Mutations (delegated to Actions) ---

    public function create(array $data): Product
    {
        return ($this->createProductAction)($data);
    }

    public function update(string $id, array $data): Product
    {
        return ($this->updateProductAction)($id, $data);
    }

    public function delete(string $id): void
    {
        ($this->deleteProductAction)($id);
    }
}
