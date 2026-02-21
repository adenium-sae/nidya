<?php

namespace App\Services\Inventory;

use App\Actions\Inventory\StorageLocations\CreateStorageLocationAction;
use App\Models\StorageLocation;

class StorageLocationService
{
    public function __construct(
        protected CreateStorageLocationAction $createStorageLocationAction,
    ) {}

    // --- Queries ---

    public function list(array $filters)
    {
        $query = StorageLocation::query();
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

    // --- Mutations (delegated to Actions) ---

    public function create(array $data): StorageLocation
    {
        return ($this->createStorageLocationAction)($data);
    }
}
