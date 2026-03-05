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
            $quantityAfter = (int) $data['quantity'];
            $difference = $quantityAfter - $quantityBefore;

            $stock->quantity = $quantityAfter;
            $stock->save();

            $reason = $data['reason'] ?? 'recount';
            $notes = $data['notes'] ?? null;

            $reasonLabels = [
                'damaged' => 'Dañado',
                'lost' => 'Pérdida/Robo',
                'found' => 'Hallazgo',
                'expired' => 'Caducado',
                'recount' => 'Recuento',
                'correction' => 'Corrección',
                'other' => 'Otro',
            ];

            $reasonLabel = $reasonLabels[$reason] ?? $reason;
            $movementNotes = "Corrección directa de cantidad por: {$reasonLabel}";
            if ($notes) {
                $movementNotes .= " — {$notes}";
            }

            StockMovement::create([
                'product_id' => $stock->product_id,
                'warehouse_id' => $stock->warehouse_id,
                'storage_location_id' => $stock->storage_location_id,
                'type' => 'adjustment',
                'status' => StockMovement::STATUS_PENDING,
                'quantity' => $difference,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'user_id' => $userId,
                'notes' => $movementNotes,
            ]);

            return $stock->load(['product', 'warehouse', 'storageLocation']);
        });
    }
}