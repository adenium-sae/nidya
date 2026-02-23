<?php

namespace App\Actions\Stock;

use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class UpdateStockQuantityAction
{
    public function __invoke(string $stockId, array $data, string $userId): Stock
    {
        return DB::transaction(function () use ($stockId, $data, $userId) {
            /** @var Stock $stock */
            $stock = Stock::findOrFail($stockId);

            $quantityBefore = $stock->quantity;
            $quantityAfter = $data['quantity'];
            $difference = $quantityAfter - $quantityBefore;

            $stock->quantity = $quantityAfter;
            $stock->save();

            StockMovement::create([
                'product_id' => $stock->product_id,
                'warehouse_id' => $stock->warehouse_id,
                'storage_location_id' => $stock->storage_location_id,
                'type' => 'correction',
                'status' => 'pending',
                'quantity' => $difference,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reason' => $data['reason'] ?? 'recount',
                'user_id' => $userId,
                'notes' => $data['notes'] ?? null,
            ]);

            return $stock->load(['product', 'warehouse', 'storageLocation']);
        });
    }
}
