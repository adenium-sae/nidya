<?php

namespace App\Actions\Stock;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\DB;

class TransferStockAction
{
    public function __invoke(array $data, string $userId): StockTransfer
    {
        return DB::transaction(function () use ($data, $userId) {
            $transfer = StockTransfer::create([
                'source_warehouse_id' => $data['source_warehouse_id'],
                'destination_warehouse_id' => $data['destination_warehouse_id'],
                'user_id' => $userId,
                'notes' => $data['notes'] ?? null,
                'status' => 'completed',
            ]);

            foreach ($data['items'] as $itemData) {
                $this->processTransferItem(
                    $transfer, 
                    $itemData, 
                    $data['source_warehouse_id'], 
                    $data['destination_warehouse_id'],
                    $userId
                );
            }

            return $transfer;
        });
    }

    private function processTransferItem(StockTransfer $transfer, array $itemData, string $srcWhId, string $dstWhId, string $userId): void
    {
        $productId = $itemData['product_id'];
        $qty = $itemData['quantity'];

        // 1. Decrease from source
        $sourceStock = Stock::where('product_id', $productId)
            ->where('warehouse_id', $srcWhId)
            ->where('storage_location_id', $itemData['source_location_id'] ?? null)
            ->lockForUpdate()
            ->first();

        if (!$sourceStock || $sourceStock->quantity < $qty) {
            throw new \Exception("Stock insuficiente en origen para el producto {$productId}");
        }

        $sourceStock->decrement('quantity', $qty);

        // 2. Increase in destination
        $destStock = Stock::firstOrCreate([
            'product_id' => $productId,
            'warehouse_id' => $dstWhId,
            'storage_location_id' => $itemData['destination_location_id'] ?? null,
        ], [
            'quantity' => 0,
            'reserved' => 0,
        ]);

        $destStock->increment('quantity', $qty);

        // 3. Log Movements
        StockMovement::create([
            'product_id' => $productId,
            'warehouse_id' => $srcWhId,
            'storage_location_id' => $itemData['source_location_id'] ?? null,
            'type' => 'transfer_out',
            'quantity' => -$qty,
            'quantity_before' => $sourceStock->quantity + $qty,
            'quantity_after' => $sourceStock->quantity,
            'reference_type' => StockTransfer::class,
            'reference_id' => $transfer->id,
            'user_id' => $userId,
        ]);

        StockMovement::create([
            'product_id' => $productId,
            'warehouse_id' => $dstWhId,
            'storage_location_id' => $itemData['destination_location_id'] ?? null,
            'type' => 'transfer_in',
            'quantity' => $qty,
            'quantity_before' => $destStock->quantity - $qty,
            'quantity_after' => $destStock->quantity,
            'reference_type' => StockTransfer::class,
            'reference_id' => $transfer->id,
            'user_id' => $userId,
        ]);
    }
}
