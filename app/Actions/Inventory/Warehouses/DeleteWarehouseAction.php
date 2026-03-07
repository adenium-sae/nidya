<?php

namespace App\Actions\Inventory\Warehouses;

use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Exceptions\Inventory\Warehouses\WarehouseNotFoundException;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;

class DeleteWarehouseAction
{
    public function __invoke(string $id): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        /** @var Warehouse|null $warehouse */
        $warehouse = Warehouse::find($id);
        if (!$warehouse) {
            throw new WarehouseNotFoundException();
        }

        // Authorization check
        $storeIds = $warehouse->stores()->pluck('stores.id')->toArray();
        foreach ($storeIds as $storeId) {
            if (!$user->hasPermissionInStore('settings.branches', $storeId)) {
                throw new AccessDeniedException();
            }
        }

        $warehouse->delete();
    }
}
