<?php

namespace App\Services\Stock;

use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function list(array $filters, int $perPage)
    {
        $query = Stock::with(['product', 'warehouse', 'storageLocation']);
        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }
        if (!empty($filters['low_stock'])) {
            $query->whereHas('product', function($q) {
                $q->whereRaw('stock.quantity <= products.min_stock');
            });
        }

        return $query->paginate($perPage);
    }

    public function adjust(array $data, int $userId): StockAdjustment
    {
        return DB::transaction(function () use ($data, $userId) {
            $tenantId = session('tenant_id');
            $year = now()->year;
            $lastAdjustment = StockAdjustment::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->first();
            $number = $lastAdjustment ? ((int) substr($lastAdjustment->folio, -5)) + 1 : 1;
            $folio = 'ADJ-' . $year . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
            $adjustment = StockAdjustment::create([
                'tenant_id' => $tenantId,
                'folio' => $folio,
                'warehouse_id' => $data['warehouse_id'],
                'type' => $data['type'],
                'reason' => $data['reason'],
                'user_id' => $userId,
                'notes' => $data['notes'] ?? null,
            ]);
            foreach ($data['items'] as $itemData) {
                $this->processAdjustmentItem($adjustment, $itemData, $data['warehouse_id'], $data['reason'], $tenantId, $userId);
            }
            return $adjustment;
        });
    }

    protected function processAdjustmentItem(StockAdjustment $adjustment, array $itemData, string $warehouseId, string $reason, string $tenantId, int $userId): void
    {
        $stock = Stock::where('product_id', $itemData['product_id'])
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();
        $quantityBefore = $stock ? $stock->quantity : 0;
        $quantityAfter = $itemData['quantity_after'];
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
                'tenant_id' => $tenantId,
                'product_id' => $itemData['product_id'],
                'warehouse_id' => $warehouseId,
                'quantity' => $quantityAfter,
                'reserved' => 0,
            ]);
        }
        StockMovement::create([
            'tenant_id' => $tenantId,
            'product_id' => $itemData['product_id'],
            'warehouse_id' => $warehouseId,
            'storage_location_id' => $stock->storage_location_id,
            'type' => 'adjustment',
            'quantity' => $difference,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'notes' => __('messages.stock_adjustment_note', ['reason' => $reason]),
            'user_id' => $userId,
            'movable_type' => StockAdjustment::class,
            'movable_id' => $adjustment->id,
        ]);
    }

    public function listMovements(array $filters, int $perPage)
    {
        $query = StockMovement::with(['product', 'warehouse', 'user']);
        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }
        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        return $query->latest()->paginate($perPage);
    }
}
