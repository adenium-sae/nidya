<?php

namespace App\Services\Stock;

use App\Actions\Stock\AdjustStockAction;
use App\Actions\Stock\TransferStockAction;
use App\Actions\Stock\UpdateStockQuantityAction;
use App\Actions\Stock\ConfirmStockMovementAction;
use App\Actions\Stock\ConfirmStockAdjustmentAction;
use App\Actions\Stock\ConfirmStockTransferAction;
use App\Actions\Stock\CancelStockMovementAction;
use App\Actions\Stock\CancelStockTransferAction;
use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function __construct(
        protected AdjustStockAction $adjustStockAction,
        protected TransferStockAction $transferStockAction,
        protected UpdateStockQuantityAction $updateStockQuantityAction,
        protected ConfirmStockMovementAction $confirmStockMovementAction,
        protected ConfirmStockAdjustmentAction $confirmStockAdjustmentAction,
        protected ConfirmStockTransferAction $confirmStockTransferAction,
        protected CancelStockMovementAction $cancelStockMovementAction,
        protected CancelStockTransferAction $cancelStockTransferAction,
    ) {}

    // --- Queries ---

    public function list(array $filters, int $perPage)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $accessibleStoreIds = $user->getAccessibleStoreIds('inventory.view');

        $query = Stock::with(['product', 'warehouse', 'storageLocation'])
            ->whereHas('warehouse.stores', function ($q) use ($accessibleStoreIds) {
                $q->whereIn('stores.id', $accessibleStoreIds);
            });

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
            $query->whereHas('product', function ($q) {
                $q->whereRaw('stock.quantity <= products.min_stock');
            });
        }

        return $query->paginate($perPage);
    }

    public function listMovements(array $filters, int $perPage)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $accessibleStoreIds = $user->getAccessibleStoreIds('inventory.view');

        $query = StockMovement::with(['product', 'warehouse', 'user'])
            ->whereHas('warehouse.stores', function ($q) use ($accessibleStoreIds) {
                $q->whereIn('stores.id', $accessibleStoreIds);
            });

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
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $accessibleStoreIds = $user->getAccessibleStoreIds('inventory.view');

        $query = StockAdjustment::with(['warehouse', 'user', 'items.product'])
            ->whereHas('warehouse.stores', function ($q) use ($accessibleStoreIds) {
                $q->whereIn('stores.id', $accessibleStoreIds);
            });

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
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $accessibleStoreIds = $user->getAccessibleStoreIds('inventory.view');

        $query = StockTransfer::with(['sourceWarehouse', 'destinationWarehouse', 'requestedBy', 'items.product'])
            ->where(function ($q) use ($accessibleStoreIds) {
                $q->whereHas('sourceWarehouse.stores', function ($sq) use ($accessibleStoreIds) {
                    $sq->whereIn('stores.id', $accessibleStoreIds);
                })->orWhereHas('destinationWarehouse.stores', function ($dq) use ($accessibleStoreIds) {
                    $dq->whereIn('stores.id', $accessibleStoreIds);
                });
            });

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

    public function adjust(array $data): StockAdjustment
    {
        return ($this->adjustStockAction)($data);
    }

    public function transfer(array $data): StockTransfer
    {
        return ($this->transferStockAction)($data);
    }

    public function updateQuantity(Stock $stock, float $newQuantity, string $reason, ?string $storageLocationId = null): Stock
    {
        return ($this->updateStockQuantityAction)($stock, $newQuantity, $reason, $storageLocationId);
    }

    public function confirmMovement(StockMovement $movement): StockMovement
    {
        return ($this->confirmStockMovementAction)($movement);
    }

    public function confirmAdjustment(string $adjustmentId): StockAdjustment
    {
        return ($this->confirmStockAdjustmentAction)($adjustmentId);
    }

    public function confirmTransfer(string $transferId): StockTransfer
    {
        return ($this->confirmStockTransferAction)($transferId);
    }

    public function cancelMovement(StockMovement $movement): StockMovement
    {
        return ($this->cancelStockMovementAction)($movement);
    }

    public function cancelTransfer(string $transferId): StockTransfer
    {
        return ($this->cancelStockTransferAction)($transferId);
    }
}
