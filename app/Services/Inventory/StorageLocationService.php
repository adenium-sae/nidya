<?php

namespace App\Services\Inventory;

use App\Actions\Inventory\StorageLocations\CreateStorageLocationAction;
use App\Models\StorageLocation;

use Illuminate\Support\Facades\Auth;

class StorageLocationService
{
    public function __construct(
        protected CreateStorageLocationAction $createStorageLocationAction,
    ) {}

    public function list(array $filters)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $accessibleStoreIds = $user->getAccessibleStoreIds('inventory.view');

        $query = StorageLocation::query()
            ->whereHas('warehouse.stores', function ($q) use ($accessibleStoreIds) {
                $q->whereIn('stores.id', $accessibleStoreIds);
            });

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
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

    // --- Mutations (delegated to Actions) ---

    public function create(array $data): StorageLocation
    {
        return ($this->createStorageLocationAction)($data);
    }
}
