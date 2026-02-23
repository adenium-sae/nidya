<?php

namespace App\Actions\Stock;

use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class AdjustStockAction
{
    public function __invoke(array $data, string $userId): StockAdjustment
    {
        return DB::transaction(function () use ($data, $userId) {
            $year = now()->year;
            $lastAdjustment = StockAdjustment::whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->first();

            $number = $lastAdjustment ? ((int) substr($lastAdjustment->folio, -5)) + 1 : 1;
            $folio = 'ADJ-' . $year . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);

            $adjustment = StockAdjustment::create([
                'folio' => $folio,
                'warehouse_id' => $data['warehouse_id'],
                'storage_location_id' => $data['storage_location_id'] ?? null,
                'type' => $data['type'],
                'status' => 'pending',
                'reason' => $data['reason'] ?? $data['items'][0]['reason'] ?? 'other',
                'user_id' => $userId,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $itemData) {
                $this->processAdjustmentItem(
                    $adjustment, 
                    $itemData, 
                    $data['warehouse_id'], 
                    $data['storage_location_id'] ?? null,
                    $itemData['reason'] ?? $data['reason'] ?? 'other', 
                    $userId
                );
            }

            return $adjustment;
        });
    }

    private function processAdjustmentItem(StockAdjustment $adjustment, array $itemData, string $warehouseId, ?string $storageLocationId, string $reason, string $userId): void
    {
        $stock = Stock::where('product_id', $itemData['product_id'])
            ->where('warehouse_id', $warehouseId)
            ->when($storageLocationId, function($q) use ($storageLocationId) {
                $q->where('storage_location_id', $storageLocationId);
            })
            ->when(!$storageLocationId, function($q) {
                $q->whereNull('storage_location_id');
            })
            ->lockForUpdate()
            ->first();

        $quantityBefore = $stock ? $stock->quantity : 0;
        $mode = $itemData['mode'] ?? 'absolute';
        $inputQuantity = $itemData['quantity'] ?? $itemData['quantity_after'] ?? 0;

        $quantityAfter = match ($mode) {
            'increment' => $quantityBefore + $inputQuantity,
            'decrement' => max(0, $quantityBefore - $inputQuantity),
            'absolute' => $inputQuantity,
            default => $inputQuantity,
        };

        $difference = $quantityAfter - $quantityBefore;

        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $itemData['product_id'],
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
        ]);

        if ($stock) {
            $stock->quantity = $quantityAfter;
            $stock->save();
        } else {
            $stock = Stock::create([
                'product_id' => $itemData['product_id'],
                'warehouse_id' => $warehouseId,
                'storage_location_id' => $storageLocationId,
                'quantity' => $quantityAfter,
                'reserved' => 0,
            ]);
        }

        StockMovement::create([
            'product_id' => $itemData['product_id'],
            'warehouse_id' => $warehouseId,
            'storage_location_id' => $storageLocationId,
            'type' => 'adjustment',
            'status' => 'pending',
            'quantity' => $difference,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'notes' => __('messages.stock_adjustment_note', ['reason' => $reason]),
            'user_id' => $userId,
            'movable_type' => StockAdjustment::class,
            'movable_id' => $adjustment->id,
        ]);
    }
}
