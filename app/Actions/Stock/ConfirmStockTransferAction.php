<?php

namespace App\Actions\Stock;

use App\Models\StockMovement;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\Access\Auth\AccessDeniedException;

class ConfirmStockTransferAction
{
    public function __invoke(string $transferId): StockTransfer
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return DB::transaction(function () use ($transferId, $user) {
            $transfer = StockTransfer::findOrFail($transferId);

            // Authorization check (Confirming a transfer means receiving it)
            $destWarehouse = $transfer->destinationWarehouse;
            $stores = $destWarehouse->stores()->pluck('stores.id')->toArray();
            $hasPermission = false;
            foreach ($stores as $storeId) {
                if ($user->hasPermissionInStore('inventory.receive', $storeId)) {
                    $hasPermission = true;
                    break;
                }
            }
            if (!$hasPermission) {
                throw new AccessDeniedException();
            }

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
}
