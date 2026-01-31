<?php

namespace App\Actions\Sales;

use App\Exceptions\Sales\SaleCancellationException;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class CancelSaleAction
{
    public function __invoke(Sale $sale, int $userId): Sale
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

    private function restoreStock(SaleItem $item, string $warehouseId, int $userId, Sale $sale): void
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
}
