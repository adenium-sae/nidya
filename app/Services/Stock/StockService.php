<?php

namespace App\Services\Stock;

use App\Actions\Stock\AdjustStockAction;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StockService
{
    public function __construct(
        protected AdjustStockAction $adjustStockAction
    ) {}

    public function list(array $filters, int $perPage)
    {
        $query = Stock::with(['product', 'warehouse', 'storageLocation']);
        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }
        
        if (array_key_exists('storage_location_id', $filters)) {
            $loc = $filters['storage_location_id'];
            if ($loc === 'null' || is_null($loc) || $loc === '') {
                $query->whereNull('storage_location_id');
            } else {
                $query->where('storage_location_id', $loc);
            }
        }

        if (!empty($filters['low_stock'])) {
            $query->whereHas('product', function($q) {
                $q->whereRaw('stock.quantity <= products.min_stock');
            });
        }

        return $query->get(); // Standard admin view often doesn't need pagination for simple tables, but we'll keep it flexible if needed. Actually StockPage expects data property or raw array.
    }

    public function adjust(array $data, string $userId): StockAdjustment
    {
        return ($this->adjustStockAction)($data, $userId);
    }

    public function updateQuantity(string $stockId, array $data, string $userId): Stock
    {
        return DB::transaction(function () use ($stockId, $data, $userId) {
            /** @var Stock $stock */
            $stock = Stock::findOrFail($stockId);
            $tenantId = $stock->tenant_id;

            $quantityBefore = $stock->quantity;
            $quantityAfter = $data['quantity'];
            $difference = $quantityAfter - $quantityBefore;

            $stock->quantity = $quantityAfter;
            $stock->save();

            StockMovement::create([
                'tenant_id' => $tenantId,
                'product_id' => $stock->product_id,
                'warehouse_id' => $stock->warehouse_id,
                'storage_location_id' => $stock->storage_location_id,
                'type' => 'correction',
                'quantity' => $difference,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reason' => $data['reason'] ?? 'recount',
                'user_id' => $userId,
                'notes' => $data['notes'] ?? null,
            ]);

            return $stock->load(['product', 'warehouse', 'storageLocation']);
        });
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

    public function listAdjustments(array $filters, int $perPage)
    {
        $query = StockAdjustment::with(['warehouse', 'user', 'items.product']);
        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['reason'])) {
            $query->where('reason', $filters['reason']);
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
