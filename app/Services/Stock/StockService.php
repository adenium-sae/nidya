<?php

namespace App\Services\Stock;

use App\Actions\Stock\AdjustStockAction;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockMovement;

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
        if (!empty($filters['low_stock'])) {
            $query->whereHas('product', function($q) {
                $q->whereRaw('stock.quantity <= products.min_stock');
            });
        }

        return $query->paginate($perPage);
    }

    public function adjust(array $data, int $userId): StockAdjustment
    {
        return ($this->adjustStockAction)($data, $userId);
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

