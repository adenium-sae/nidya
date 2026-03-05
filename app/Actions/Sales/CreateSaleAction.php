<?php

namespace App\Actions\Sales;

use App\Exceptions\Catalog\Products\ProductNotAvailableException;
use App\Exceptions\Inventory\Stock\InsufficientStockException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class CreateSaleAction
{
    public function __invoke(array $data, int $userId): Sale
    {
        return DB::transaction(function () use ($data, $userId) {
            $folio = Sale::generateFolio();
            $sale = Sale::create([
                'folio' => $folio,
                'store_id' => $data['store_id'],
                'branch_id' => $data['branch_id'],
                'warehouse_id' => $data['warehouse_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => $userId,
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
                $this->processSaleItem($sale, $itemData, $data['store_id'], $data['warehouse_id'], $userId);
            }
            $sale->calculateTotals();
            $sale->complete();
            return $sale;
        });
    }

    private function processSaleItem(Sale $sale, array $itemData, string $storeId, string $warehouseId, int $userId): void
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
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ]);
        if ($product->track_inventory) {
            $this->decrementStock($product, $warehouseId, $quantity, $userId, $sale);
        }
    }

    private function decrementStock(Product $product, string $warehouseId, int $quantity, int $userId, Sale $sale): void
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
            'user_id' => $userId,
            'movable_type' => Sale::class,
            'movable_id' => $sale->id,
        ]);
    }
}
