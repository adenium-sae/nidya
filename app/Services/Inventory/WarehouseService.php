<?php

namespace App\Services\Inventory;

use App\Exceptions\Inventory\Warehouses\WarehouseNotFoundException;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;

class WarehouseService
{
    public function findAll(array $filters)
    {
        $query = Warehouse::with(['store', 'branch', 'address']);
        $tenantId = session('tenant_id') ?? Auth::user()->tenants()->first()?->id;
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
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
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }
        return $query->get();
    }

    public function getById(string $id): Warehouse
    {
        $warehouse = Warehouse::with(['store', 'branch', 'address', 'storageLocations', 'stock'])->find($id);
        if (!$warehouse) {
            throw new WarehouseNotFoundException();
        }
        return $warehouse;
    }

    public function create(array $data): Warehouse
    {
        $tenantId = session('tenant_id') ?? Auth::user()->tenants()->first()?->id;
        if (!$tenantId) {
            throw new \Exception("No tenant context found for creating warehouse.");
        }
        $data['tenant_id'] = $tenantId;
        return Warehouse::create($data);
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

    public function delete(string $id): void
    {
        $warehouse = Warehouse::find($id);
        if (!$warehouse) {
            throw new WarehouseNotFoundException();
        }
        $warehouse->delete();
    }
}

