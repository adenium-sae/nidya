<?php

namespace App\Actions\Stock;

use App\Models\StockMovement;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\Access\Auth\AccessDeniedException;

class CancelStockTransferAction
{
    public function __invoke(string $transferId): StockTransfer
    {
        return DB::transaction(function () use ($transferId) {
            $transfer = StockTransfer::findOrFail($transferId);
            /** @var \App\Models\User $user */
            $user = Auth::user();

            // Authorization check
            $sourceWarehouse = $transfer->sourceWarehouse;
            $stores = $sourceWarehouse->stores()->pluck('stores.id')->toArray();
            $hasPermission = false;
            foreach ($stores as $storeId) {
                if ($user->hasPermissionInStore('inventory.transfer', $storeId)) {
                    $hasPermission = true;
                    break;
                }
            }
            if (!$hasPermission) {
                throw new AccessDeniedException();
            }

            if ($transfer->status !== StockTransfer::STATUS_PENDING) {
                throw new \Exception('Solo se pueden cancelar transferencias pendientes.');
            }

            $transfer->status = StockTransfer::STATUS_CANCELLED;
            $transfer->save();

            // Cancel all related pending movements
            StockMovement::where('movable_type', StockTransfer::class)
                ->where('movable_id', $transferId)
                ->where('status', StockMovement::STATUS_PENDING)
                ->update(['status' => StockMovement::STATUS_CANCELLED]);

            return $transfer->load(['sourceWarehouse', 'destinationWarehouse', 'items.product']);
        });
    }
}
