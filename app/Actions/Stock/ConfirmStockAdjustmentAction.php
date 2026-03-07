<?php

namespace App\Actions\Stock;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\Access\Auth\AccessDeniedException;

class ConfirmStockAdjustmentAction
{
    public function __invoke(string $adjustmentId): StockAdjustment
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return DB::transaction(function () use ($adjustmentId, $user) {
            $adjustment = StockAdjustment::findOrFail($adjustmentId);

            // Authorization check
            $warehouse = $adjustment->warehouse;
            $stores = $warehouse->stores()->pluck('stores.id')->toArray();
            $hasPermission = false;
            foreach ($stores as $storeId) {
                if ($user->hasPermissionInStore('inventory.adjust', $storeId)) {
                    $hasPermission = true;
                    break;
                }
            }
            if (!$hasPermission) {
                throw new AccessDeniedException();
            }

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
}
