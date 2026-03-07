<?php

namespace App\Actions\Stock;

use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateStockQuantityAction
{
    public function __invoke(Stock $stock, float $newQuantity, string $reason, ?string $storageLocationId = null): Stock
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return DB::transaction(function () use ($stock, $newQuantity, $reason, $storageLocationId, $user) {
            // Eager load warehouse and its stores for permission check
            $stock->load('warehouse.stores');
            $warehouse = $stock->warehouse;

            // Authorization check
            $hasPermission = false;
            foreach ($warehouse->stores as $store) {
                if ($user->hasPermissionInStore('inventory.adjust', $store->id)) {
                    $hasPermission = true;
                    break;
                }
            }

            if (!$hasPermission) {
                throw new AccessDeniedException();
            }

            $oldQuantity = $stock->quantity;
            $stock->quantity = $newQuantity;
            if ($storageLocationId) {
                $stock->storage_location_id = $storageLocationId;
            }
            $stock->save();


            StockMovement::create([
                'product_id' => $stock->product_id,
                'warehouse_id' => $stock->warehouse_id,
                'storage_location_id' => $stock->storage_location_id,
                'type' => $newQuantity > $oldQuantity ? StockMovement::TYPE_ENTRY : StockMovement::TYPE_EXIT,
                'quantity' => abs($newQuantity - $oldQuantity),
                'quantity_before' => $oldQuantity,
                'quantity_after' => $newQuantity,
                'reason' => $reason,
                'notes' => "Actualización manual de stock",
                'user_id' => $user->id,
                'status' => StockMovement::STATUS_COMPLETED,
            ]);

            return $stock->load(['product', 'warehouse', 'storageLocation']);
        });
    }
}
