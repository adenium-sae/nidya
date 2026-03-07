<?php

namespace App\Services\Inventory;

use App\Actions\Inventory\Warehouses\CreateWarehouseAction;
use App\Actions\Inventory\Warehouses\DeleteWarehouseAction;
use App\Actions\Inventory\Warehouses\UpdateWarehouseAction;
use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;

class WarehouseService
{
    public function __construct(
        protected CreateWarehouseAction $createWarehouseAction,
        protected UpdateWarehouseAction $updateWarehouseAction,
        protected DeleteWarehouseAction $deleteWarehouseAction,
    ) {}

    // --- Queries ---

    public function findAll(array $filters)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $accessibleStoreIds = $user->getAccessibleStoreIds('inventory.view');

        $query = Warehouse::with(['stores', 'branch', 'address'])
            ->whereHas('stores', function ($q) use ($accessibleStoreIds) {
                $q->whereIn('stores.id', $accessibleStoreIds);
            });

        if (!empty($filters['store_id'])) {
            $query->whereHas('stores', function ($q) use ($filters) {
                $q->where('stores.id', $filters['store_id']);
            });
        }
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(code) LIKE ?', ["%{$search}%"]);
            });
        }
        return $query->get();
    }

    public function getById(string $id): Warehouse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $warehouse = Warehouse::with(['stores', 'branch', 'address', 'storageLocations', 'stock'])->findOrFail($id);

        // Authorization check
        $accessibleStoreIds = $user->getAccessibleStoreIds('inventory.view');
        $warehouseStoreIds = $warehouse->stores()->pluck('stores.id')->toArray();
        if (empty(array_intersect($accessibleStoreIds, $warehouseStoreIds))) {
            throw new AccessDeniedException();
        }

        return $warehouse;
    }

    // --- Mutations (delegated to Actions) ---

    public function create(array $data): Warehouse
    {
        return ($this->createWarehouseAction)($data);
    }

    public function update(string $id, array $data): Warehouse
    {
        return ($this->updateWarehouseAction)($id, $data);
    }

    public function delete(string $id): void
    {
        ($this->deleteWarehouseAction)($id);
    }
}
