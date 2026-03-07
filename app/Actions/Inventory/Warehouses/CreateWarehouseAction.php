<?php

namespace App\Actions\Inventory\Warehouses;

use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateWarehouseAction
{
    public function __invoke(array $data): Warehouse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Authorization check
        $storeIds = $data['store_ids'] ?? [];
        if (empty($storeIds)) {
            abort(422, 'Debe asignar al menos una tienda al almacén.');
        }

        foreach ($storeIds as $storeId) {
            if (!$user->hasPermissionInStore('settings.branches', $storeId)) {
                throw new AccessDeniedException();
            }
        }

        return DB::transaction(function () use ($data) {
            $warehouse = Warehouse::create($data);

            if (isset($data['store_ids']) && is_array($data['store_ids'])) {
                $warehouse->stores()->sync($data['store_ids']);
            }

            return $warehouse->load(['stores', 'branch', 'address']);
        });
    }
}
