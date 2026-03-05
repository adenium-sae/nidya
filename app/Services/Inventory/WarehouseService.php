<?php

namespace App\Services\Inventory;

use App\Actions\Inventory\Warehouses\CreateWarehouseAction;
use App\Actions\Inventory\Warehouses\DeleteWarehouseAction;
use App\Actions\Inventory\Warehouses\UpdateWarehouseAction;
use App\Exceptions\Inventory\Warehouses\WarehouseNotFoundException;
use App\Models\Warehouse;

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
        $query = Warehouse::with(['stores', 'branch', 'address']);
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
        $warehouse = Warehouse::with(['stores', 'branch', 'address', 'storageLocations', 'stock'])->find($id);
        if (!$warehouse) {
            throw new WarehouseNotFoundException();
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
