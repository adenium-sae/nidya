<?php

namespace App\Actions\Inventory\StorageLocations;

use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Models\StorageLocation;
use Illuminate\Support\Facades\Auth;

class CreateStorageLocationAction
{
    public function __invoke(array $data): StorageLocation
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Authorization check
        $warehouse = \App\Models\Warehouse::findOrFail($data['warehouse_id']);
        $storeIds = $warehouse->stores()->pluck('stores.id')->toArray();
        $hasPermission = false;
        foreach ($storeIds as $storeId) {
            if ($user->hasPermissionInStore('inventory.adjust', $storeId)) {
                $hasPermission = true;
                break;
            }
        }
        if (!$hasPermission) {
            throw new AccessDeniedException();
        }

        return StorageLocation::create($data);
    }
}
