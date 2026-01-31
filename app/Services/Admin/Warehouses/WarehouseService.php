<?php

namespace App\Services\Admin\Warehouses;

use App\Exceptions\Inventory\Warehouses\WarehouseNotFoundException;
use App\Models\Warehouse;

class WarehouseService
{
    public function findAll(array $filters)
    {
        $query = Warehouse::with(['store', 'branch', 'address']);

        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->get();
    }

    public function update(string $id, array $data): Warehouse
    {
        /** @var Warehouse|null $warehouse */
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            throw new WarehouseNotFoundException();
        }

        $warehouse->fill($data);
        $warehouse->save();
        return $warehouse->fresh(['store', 'branch', 'address']);
    }

    public function getById(string $id): Warehouse
    {
        $warehouse = Warehouse::with(['store', 'branch', 'address', 'storageLocations', 'stock'])->find($id);
        if (!$warehouse) {
            throw new WarehouseNotFoundException();
        }
        return $warehouse;
    }
}
