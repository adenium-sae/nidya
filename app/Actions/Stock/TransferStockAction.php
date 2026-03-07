<?php

namespace App\Actions\Stock;

use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Exceptions\Inventory\Stock\InsufficientStockException;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferStockAction
{
    public function __invoke(array $data): StockTransfer
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Authorization check
        $sourceWarehouse = Warehouse::with('stores')->findOrFail($data['source_warehouse_id']);
        $destinationWarehouse = Warehouse::with('stores')->findOrFail($data['destination_warehouse_id']);

        // Check permission: user must have 'inventory.transfer' in at least one store associated with the source warehouse
        $hasPermission = false;
        foreach ($sourceWarehouse->stores as $store) {
            if ($user->hasPermissionInStore('inventory.transfer', $store->id)) {
                $hasPermission = true;
                break;
            }
        }
        if (!$hasPermission) {
            throw new AccessDeniedException('No tienes permiso para iniciar transferencias desde este almacén.');
        }

        return DB::transaction(function () use ($data, $user) {
            $folio = $this->generateFolio();

            $transfer = StockTransfer::create([
                'folio' => $folio,
                'from_warehouse_id' => $data['source_warehouse_id'],
                'to_warehouse_id' => $data['destination_warehouse_id'],
                'requested_by' => $user->id,
                'notes' => $data['notes'] ?? null,
                'status' => StockTransfer::STATUS_PENDING,
            ]);

            foreach ($data['items'] as $itemData) {
                $this->processTransferItem(
                    $transfer,
                    $itemData,
                    $data['source_warehouse_id'],
                    $data['destination_warehouse_id'],
                    $user
                );
            }

            return $transfer->load(['sourceWarehouse', 'destinationWarehouse', 'items']);
        });
    }

    private function generateFolio(): string
    {
        $year = now()->year;
        $lastTransfer = StockTransfer::where('folio', 'like', "TRF-{$year}-%")
            ->orderByDesc('folio')
            ->first();

        $number = 1;
        if ($lastTransfer && preg_match('/(\d{5})$/', $lastTransfer->folio, $matches)) {
            $number = ((int) $matches[1]) + 1;
        }

        return 'TRF-' . $year . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    private function processTransferItem(
        StockTransfer $transfer,
        array $itemData,
        string $srcWhId,
        string $dstWhId,
        \App\Models\User $user
    ): void {
        $productId = $itemData['product_id'];
        $qty = (int) $itemData['quantity'];
        $sourceLocationId = $itemData['source_location_id'] ?? null;
        $destLocationId = $itemData['destination_location_id'] ?? null;

        // 1. Lock and validate source stock
        $sourceStockQuery = Stock::where('product_id', $productId)
            ->where('warehouse_id', $srcWhId);

        if ($sourceLocationId) {
            $sourceStockQuery->where('storage_location_id', $sourceLocationId);
        } else {
            $sourceStockQuery->whereNull('storage_location_id');
        }

        $sourceStock = $sourceStockQuery->lockForUpdate()->first();

        if (!$sourceStock || $sourceStock->quantity < $qty) {
            $available = $sourceStock ? $sourceStock->quantity : 0;
            throw new \Exception(
                "Stock insuficiente en origen para el producto {$productId}. " .
                    "Disponible: {$available}, solicitado: {$qty}"
            );
        }

        $sourceQtyBefore = $sourceStock->quantity;
        $sourceStock->decrement('quantity', $qty);
        $sourceQtyAfter = $sourceStock->fresh()->quantity;

        // 2. Increase in destination (create if not exists)
        $destStockQuery = Stock::where('product_id', $productId)
            ->where('warehouse_id', $dstWhId);

        if ($destLocationId) {
            $destStockQuery->where('storage_location_id', $destLocationId);
        } else {
            $destStockQuery->whereNull('storage_location_id');
        }

        $destStock = $destStockQuery->lockForUpdate()->first();

        if (!$destStock) {
            $destStock = Stock::create([
                'product_id' => $productId,
                'warehouse_id' => $dstWhId,
                'storage_location_id' => $destLocationId,
                'quantity' => 0,
                'reserved' => 0,
            ]);
        }

        $destQtyBefore = $destStock->quantity;
        $destStock->increment('quantity', $qty);
        $destQtyAfter = $destStock->fresh()->quantity;

        // 3. Create transfer item record
        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $productId,
            'quantity_requested' => $qty,
            'quantity_sent' => $qty,
        ]);

        // 4. Log stock movements using valid enum type 'transfer' and morphs
        $outMovement = StockMovement::create([
            'product_id' => $productId,
            'warehouse_id' => $srcWhId,
            'storage_location_id' => $sourceLocationId,
            'type' => 'transfer',
            'status' => StockMovement::STATUS_PENDING,
            'quantity' => -$qty,
            'quantity_before' => $sourceQtyBefore,
            'quantity_after' => $sourceQtyAfter,
            'notes' => "Transferencia salida → almacén destino (folio: {$transfer->folio})",
            'user_id' => $user->id,
            'movable_type' => StockTransfer::class,
            'movable_id' => $transfer->id,
        ]);

        $inMovement = StockMovement::create([
            'product_id' => $productId,
            'warehouse_id' => $dstWhId,
            'storage_location_id' => $destLocationId,
            'type' => 'transfer',
            'status' => StockMovement::STATUS_PENDING,
            'quantity' => $qty,
            'quantity_before' => $destQtyBefore,
            'quantity_after' => $destQtyAfter,
            'notes' => "Transferencia entrada ← almacén origen (folio: {$transfer->folio})",
            'user_id' => $user->id,
            'movable_type' => StockTransfer::class,
            'movable_id' => $transfer->id,
            'related_movement_id' => $outMovement->id,
        ]);

        // Link the out movement to the in movement
        $outMovement->update(['related_movement_id' => $inMovement->id]);
    }
}
