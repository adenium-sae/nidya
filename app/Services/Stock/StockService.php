<?php

namespace App\Services\Stock;

use App\Actions\Stock\AdjustStockAction;
use App\Actions\Stock\TransferStockAction;
use App\Actions\Stock\UpdateStockQuantityAction;
use App\Actions\Stock\ConfirmMovementAction;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function __construct(
        protected AdjustStockAction $adjustStockAction,
        protected TransferStockAction $transferStockAction,
        protected UpdateStockQuantityAction $updateStockQuantityAction,
        protected ConfirmMovementAction $confirmMovementAction,
    ) {}

    // --- Queries ---

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

        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $query->whereHas('product', function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(barcode) LIKE ?', ["%{$search}%"]);
            });
        }

        if (!empty($filters['low_stock'])) {
            $query->whereHas('product', function($q) {
                $q->whereRaw('stock.quantity <= products.min_stock');
            });
        }

        return $query->paginate($perPage);
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

    public function listTransfers(array $filters, int $perPage)
    {
        $query = StockTransfer::with(['sourceWarehouse', 'destinationWarehouse', 'requestedBy', 'items.product']);

        if (!empty($filters['from_warehouse_id'])) {
            $query->where('from_warehouse_id', $filters['from_warehouse_id']);
        }
        if (!empty($filters['to_warehouse_id'])) {
            $query->where('to_warehouse_id', $filters['to_warehouse_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate($perPage);
    }

    // --- Mutations (delegated to Actions) ---

    public function adjust(array $data, string $userId): StockAdjustment
    {
        return ($this->adjustStockAction)($data, $userId);
    }

    public function transfer(array $data, string $userId): StockTransfer
    {
        return ($this->transferStockAction)($data, $userId);
    }

    public function updateQuantity(string $stockId, array $data, string $userId): Stock
    {
        return ($this->updateStockQuantityAction)($stockId, $data, $userId);
    }

    public function confirmMovement(string $movementId): StockMovement
    {
        return $this->confirmMovementAction->confirmMovement($movementId);
    }

    public function confirmAdjustment(string $adjustmentId): StockAdjustment
    {
        return $this->confirmMovementAction->confirmAdjustment($adjustmentId);
    }

    public function confirmTransfer(string $transferId): StockTransfer
    {
        return $this->confirmMovementAction->confirmTransfer($transferId);
    }

    public function cancelMovement(string $movementId): StockMovement
    {
        return $this->confirmMovementAction->cancelMovement($movementId);
    }

    public function cancelTransfer(string $transferId): StockTransfer
    {
        return DB::transaction(function () use ($transferId) {
            $transfer = StockTransfer::findOrFail($transferId);

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
