<?php

namespace App\Actions\Stock;

use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\Access\Auth\AccessDeniedException;

class CancelStockMovementAction
{
    public function __invoke(StockMovement $movement): StockMovement
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return DB::transaction(function () use ($movement, $user) {
            // Resolve warehouse to check permissions
            $warehouseId = $movement->warehouse_id;
            $warehouse = Warehouse::with('stores')->findOrFail($warehouseId);

            foreach ($warehouse->stores as $store) {
                if (!$user->hasPermissionInStore('inventory.adjust', $store->id)) {
                    throw new AccessDeniedException();
                }
            }

            if ($movement->status !== StockMovement::STATUS_PENDING) {
                throw new \Exception('Solo se pueden cancelar movimientos pendientes.');
            }

            $movement->status = StockMovement::STATUS_CANCELLED;
            $movement->save();

            return $movement;
        });
    }
}
