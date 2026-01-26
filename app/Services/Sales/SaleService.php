<?php

namespace App\Services\Sales;

use App\Exceptions\Products\ProductNotAvailableException;
use App\Exceptions\Sales\SaleCancellationException;
use App\Exceptions\Stock\InsufficientStockException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function list(array $filters, int $perPage)
    {
        $query = Sale::with(['user', 'customer', 'branch', 'items.product']);
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        return $query->latest()->paginate($perPage);
    }

    public function create(array $data, int $userId): Sale
    {
        return DB::transaction(function () use ($data, $userId) {
            $tenantId = session('tenant_id');
            $folio = Sale::generateFolio($tenantId);
            $sale = Sale::create([
                'tenant_id' => $tenantId,
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
                $this->processSaleItem($sale, $itemData, $data['store_id'], $data['warehouse_id'], $tenantId, $userId);
            }
            $sale->calculateTotals();
            $sale->complete();
            return $sale;
        });
    }

    protected function processSaleItem(Sale $sale, array $itemData, string $storeId, string $warehouseId, string $tenantId, int $userId): void
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
            $this->decrementStock($product, $warehouseId, $quantity, $tenantId, $userId, $sale);
        }
    }

    protected function decrementStock(Product $product, string $warehouseId, int $quantity, string $tenantId, int $userId, Sale $sale): void
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
            'tenant_id' => $tenantId,
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

    public function cancel(Sale $sale, int $userId): Sale
    {
        if ($sale->status === 'cancelled') {
            throw new SaleCancellationException(__('exceptions.sale_already_cancelled'));
        }
        return DB::transaction(function () use ($sale, $userId) {
            foreach ($sale->items as $item) {
                if ($item->product->track_inventory) {
                    $this->restoreStock($item, $sale->warehouse_id, $userId, $sale);
                }
            }
            $sale->cancel();
            return $sale;
        });
    }

    protected function restoreStock(SaleItem $item, string $warehouseId, int $userId, Sale $sale): void
    {
        $stock = Stock::where('product_id', $item->product_id)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();
        if ($stock) {
            $quantityBefore = $stock->quantity;
            $stock->addStock($item->quantity);
            StockMovement::create([
                'tenant_id' => session('tenant_id'),
                'product_id' => $item->product_id,
                'warehouse_id' => $warehouseId,
                'storage_location_id' => $stock->storage_location_id,
                'type' => 'return',
                'quantity' => $item->quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $stock->quantity,
                'notes' => __('messages.sale_return_note', ['folio' => $sale->folio]),
                'user_id' => $userId,
                'movable_type' => Sale::class,
                'movable_id' => $sale->id,
            ]);
        }
    }

    public function getDailySummary(string $branchId, string $date): array
    {
        $sales = Sale::where('branch_id', $branchId)
            ->whereDate('created_at', $date)
            ->where('status', 'completed')
            ->get();
        return [
            'date' => $date,
            'total_sales' => $sales->count(),
            'total_amount' => $sales->sum('total'),
            'by_payment_method' => $sales->groupBy('payment_method')->map(function($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('total'),
                ];
            }),
        ];
    }
}
