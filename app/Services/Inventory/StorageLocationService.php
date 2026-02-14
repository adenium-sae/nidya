<?php

namespace App\Services\Inventory;

use App\Models\StorageLocation;
use Illuminate\Support\Facades\Auth;

class StorageLocationService
{
    public function list(array $filters)
    {
        $tenantId = session('tenant_id') ?? Auth::user()->tenants()->first()?->id;
        $query = StorageLocation::where('tenant_id', $tenantId);
        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }
        return $query->get();
    }

    public function create(array $data): StorageLocation
    {
        $data['tenant_id'] = session('tenant_id') ?? Auth::user()->tenants()->first()?->id;
        return StorageLocation::create($data);
    }
}
