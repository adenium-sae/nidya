<?php

namespace App\Actions\Sales;

use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Exceptions\Catalog\Products\ProductNotAvailableException;
use App\Exceptions\Inventory\Stock\InsufficientStockException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateSaleAction
{
    public function __invoke(array $data): Sale
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Validation: If it's a single-store sale, we check the global store.
        // If items come from different stores, we check permissions per item in processSaleItem.
        if (isset($data['store_id']) && !$user->hasPermissionInStore('sales.create', $data['store_id'])) {
            throw new AccessDeniedException();
        }

        return DB::transaction(function () use ($data, $user) {
            $folio = Sale::generateFolio();
            $sale = Sale::create([
                'folio' => $folio,
                'store_id' => $data['store_id'],
                'branch_id' => $data['branch_id'],
                'warehouse_id' => $data['warehouse_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => $user->id,
                'payment_method' => $data['payment_method'],
                'cash_received' => $data['cash_received'] ?? null,
                'discount' => $data['discount'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'subtotal' => 0,
                'tax' => 0,
                'total' => 0,
                'status' => 'completed',
            ]);
            foreach ($data['items'] as $itemData) {
                // Determine item store: specific in itemData or fallback to sale's global store
                $itemStoreId = $itemData['store_id'] ?? $data['store_id'];
                
                // Permission check per item
                if (!$user->hasPermissionInStore('sales.create', $itemStoreId)) {
                    throw new AccessDeniedException("No permission to sell from store: {$itemStoreId}");
                }

                $this->processSaleItem($sale, $itemData, $itemStoreId, $data['warehouse_id'], $user);
            }
            $sale->calculateTotals();
            $sale->complete();
            return $sale;
        });
    }

    private function processSaleItem(Sale $sale, array $itemData, string $storeId, string $warehouseId, \App\Models\User $user): void
    {
        $product = Product::findOrFail($itemData['product_id']);
        $storeProduct = $product->storeProducts()
            ->where('store_id', $storeId)
            ->first();
        if (!$storeProduct) {
            throw new ProductNotAvailableException($product->name);
        }
        $quantity = $itemData['quantity'];
        $unitPrice = $storeProduct->price;
        $subtotal = $quantity * $unitPrice;
        $tax = $subtotal * 0.16;
        $total = $subtotal + $tax;
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'store_id' => $storeId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ]);
        if ($product->track_inventory) {
            $this->decrementStock($product, $warehouseId, $quantity, $user, $sale);
        }
    }

    private function decrementStock(Product $product, string $warehouseId, int $quantity, \App\Models\User $user, Sale $sale): void
    {
        $stock = Stock::where('product_id', $product->id)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();
        if (!$stock || $stock->getAvailableQuantity() < $quantity) {
            throw new InsufficientStockException(
                __('exceptions.insufficient_stock_for_product', ['product' => $product->name])
            );
        }
        $quantityBefore = $stock->quantity;
        $stock->removeStock($quantity);
        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseId,
            'storage_location_id' => $stock->storage_location_id,
            'type' => 'sale',
            'quantity' => -$quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $stock->quantity,
            'cost' => $stock->avg_cost,
            'user_id' => $user->id,
            'movable_type' => Sale::class,
            'movable_id' => $sale->id,
        ]);
    }
}
