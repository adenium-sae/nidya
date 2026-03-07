<?php

namespace App\Actions\Stock;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\Access\Auth\AccessDeniedException;

class ConfirmStockMovementAction
{
    public function __invoke(StockMovement $movement): StockMovement
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return DB::transaction(function () use ($movement, $user) {
            $warehouseId = $movement->destination_warehouse_id ?? $movement->warehouse_id;
            $warehouse = Warehouse::with('stores')->findOrFail($warehouseId);

            $permission = $movement->movable_type === \App\Models\StockTransfer::class ? 'inventory.receive' : 'inventory.adjust';

            $hasPermission = false;
            foreach ($warehouse->stores as $store) {
                if ($user->hasPermissionInStore($permission, $store->id)) {
                    $hasPermission = true;
                    break;
                }
            }

            if (!$hasPermission) {
                throw new AccessDeniedException();
            }

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
                    ->where('id', '!=', $movement->id)
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
}
