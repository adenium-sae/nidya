<?php

namespace App\Actions\Stock;

use App\Models\Stock;
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

            if ($movement->status === StockMovement::STATUS_COMPLETED) {
                throw new \Exception('El movimiento ya fue confirmado.');
            }

            if ($movement->status === StockMovement::STATUS_CANCELLED) {
                throw new \Exception('No se puede confirmar un movimiento cancelado.');
            }

            // If this movement belongs to an adjustment, apply the stock change
            if ($movement->movable_type === StockAdjustment::class && $movement->movable_id) {
                $stockQuery = Stock::where('product_id', $movement->product_id)
                    ->where('warehouse_id', $movement->warehouse_id);

                if ($movement->storage_location_id) {
                    $stockQuery->where('storage_location_id', $movement->storage_location_id);
                } else {
                    $stockQuery->whereNull('storage_location_id');
                }

                $stock = $stockQuery->lockForUpdate()->first();

                if ($stock) {
                    $stock->quantity = $movement->quantity_after;
                    $stock->save();
                } else {
                    Stock::create([
                        'product_id'          => $movement->product_id,
                        'warehouse_id'        => $movement->warehouse_id,
                        'storage_location_id' => $movement->storage_location_id,
                        'quantity'            => $movement->quantity_after,
                        'reserved'            => 0,
                    ]);
                }

                // If all movements for this adjustment are now completed, mark the adjustment too
                $pendingCount = StockMovement::where('movable_type', StockAdjustment::class)
                    ->where('movable_id', $movement->movable_id)
                    ->where('status', StockMovement::STATUS_PENDING)
                    ->where('id', '!=', $movementId)
                    ->count();

                if ($pendingCount === 0) {
                    StockAdjustment::where('id', $movement->movable_id)
                        ->where('status', '!=', 'completed')
                        ->update(['status' => 'completed']);
                }
            }

            $movement->status = StockMovement::STATUS_COMPLETED;
            $movement->save();

            return $movement;
        });
    }

    public function confirmAdjustment(string $adjustmentId): StockAdjustment
    {
        return DB::transaction(function () use ($adjustmentId) {
            $adjustment = StockAdjustment::findOrFail($adjustmentId);

            if ($adjustment->status === 'completed') {
                throw new \Exception('El ajuste ya fue confirmado.');
            }

            if ($adjustment->status === 'cancelled') {
                throw new \Exception('No se puede confirmar un ajuste cancelado.');
            }

            // Apply stock changes from each pending movement linked to this adjustment
            $movements = StockMovement::where('movable_type', StockAdjustment::class)
                ->where('movable_id', $adjustmentId)
                ->where('status', StockMovement::STATUS_PENDING)
                ->lockForUpdate()
                ->get();

            foreach ($movements as $movement) {
                $stockQuery = Stock::where('product_id', $movement->product_id)
                    ->where('warehouse_id', $movement->warehouse_id);

                if ($movement->storage_location_id) {
                    $stockQuery->where('storage_location_id', $movement->storage_location_id);
                } else {
                    $stockQuery->whereNull('storage_location_id');
                }

                $stock = $stockQuery->lockForUpdate()->first();

                if ($stock) {
                    $stock->quantity = $movement->quantity_after;
                    $stock->save();
                } else {
                    Stock::create([
                        'product_id' => $movement->product_id,
                        'warehouse_id' => $movement->warehouse_id,
                        'storage_location_id' => $movement->storage_location_id,
                        'quantity' => $movement->quantity_after,
                        'reserved' => 0,
                    ]);
                }

                $movement->status = StockMovement::STATUS_COMPLETED;
                $movement->save();
            }

            $adjustment->status = 'completed';
            $adjustment->save();

            return $adjustment->load(['items.product', 'warehouse']);
        });
    }

    public function confirmTransfer(string $transferId): StockTransfer
    {
        return DB::transaction(function () use ($transferId) {
            $transfer = StockTransfer::findOrFail($transferId);

            if ($transfer->status === StockTransfer::STATUS_COMPLETED) {
                throw new \Exception('La transferencia ya fue confirmada.');
            }

            if ($transfer->status === StockTransfer::STATUS_CANCELLED) {
                throw new \Exception('No se puede confirmar una transferencia cancelada.');
            }

            $transfer->status = StockTransfer::STATUS_COMPLETED;
            $transfer->received_at = now();
            $transfer->save();

            // Confirm all related movements via morph relationship
            StockMovement::where('movable_type', StockTransfer::class)
                ->where('movable_id', $transferId)
                ->where('status', StockMovement::STATUS_PENDING)
                ->update(['status' => StockMovement::STATUS_COMPLETED]);

            return $transfer->load(['sourceWarehouse', 'destinationWarehouse', 'items.product']);
        });
    }

    public function cancelMovement(string $movementId): StockMovement
    {
        return DB::transaction(function () use ($movementId) {
            $movement = StockMovement::findOrFail($movementId);

            if ($movement->status !== StockMovement::STATUS_PENDING) {
                throw new \Exception('Solo se pueden cancelar movimientos pendientes.');
            }

            $movement->status = StockMovement::STATUS_CANCELLED;
            $movement->save();

            return $movement;
        });
    }
}