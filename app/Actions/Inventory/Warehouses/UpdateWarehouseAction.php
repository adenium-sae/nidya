<?php

namespace App\Actions\Inventory\Warehouses;

use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Exceptions\Inventory\Warehouses\WarehouseNotFoundException;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateWarehouseAction
{
    public function __invoke(string $id, array $data): Warehouse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return DB::transaction(function () use ($id, $data, $user) {
            /** @var Warehouse|null $warehouse */
            $warehouse = Warehouse::find($id);
            if (!$warehouse) {
                throw new WarehouseNotFoundException();
            }

            // Authorization check: User must have permission in ALL current stores of the warehouse
            $currentStoreIds = $warehouse->stores()->pluck('stores.id')->toArray();
            foreach ($currentStoreIds as $storeId) {
                if (!$user->hasPermissionInStore('settings.branches', $storeId)) {
                    throw new AccessDeniedException();
                }
            }

            // If store_ids are being updated, check permission in new stores too
            if (isset($data['store_ids']) && is_array($data['store_ids'])) {
                foreach ($data['store_ids'] as $storeId) {
                    if (!$user->hasPermissionInStore('settings.branches', $storeId)) {
                        throw new AccessDeniedException();
                    }
                }
            }

            $warehouse->fill($data);
            $warehouse->save();

            if (isset($data['store_ids']) && is_array($data['store_ids'])) {
                $warehouse->stores()->sync($data['store_ids']);
            }

            return $warehouse->fresh(['stores', 'branch', 'address']);
        });
    }
}
