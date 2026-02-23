<?php

namespace App\Actions\Stock;

use App\Models\StockMovement;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\DB;

class ConfirmMovementAction
{
    public function confirmMovement(string $movementId): StockMovement
    {
        return DB::transaction(function () use ($movementId) {
            $movement = StockMovement::findOrFail($movementId);
            $movement->status = StockMovement::STATUS_COMPLETED;
            $movement->save();

            return $movement;
        });
    }

    public function confirmAdjustment(string $adjustmentId): StockAdjustment
    {
        return DB::transaction(function () use ($adjustmentId) {
            $adjustment = StockAdjustment::findOrFail($adjustmentId);
            $adjustment->status = 'completed';
            $adjustment->save();

            // Also confirm related movements
            StockMovement::where('movable_type', StockAdjustment::class)
                ->where('movable_id', $adjustmentId)
                ->update(['status' => StockMovement::STATUS_COMPLETED]);

            return $adjustment;
        });
    }

    public function confirmTransfer(string $transferId): StockTransfer
    {
        return DB::transaction(function () use ($transferId) {
            $transfer = StockTransfer::findOrFail($transferId);
            $transfer->status = 'completed';
            $transfer->save();

            // Also confirm related movements
            StockMovement::where('reference_type', StockTransfer::class)
                ->where('reference_id', $transferId)
                ->update(['status' => StockMovement::STATUS_COMPLETED]);

            return $transfer;
        });
    }
}
