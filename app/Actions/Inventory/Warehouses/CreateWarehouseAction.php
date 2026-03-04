<?php

namespace App\Actions\Inventory\Warehouses;

use App\Models\Warehouse;

use Illuminate\Support\Facades\DB;

class CreateWarehouseAction
{
    public function __invoke(array $data): Warehouse
    {
        return DB::transaction(function () use ($data) {
            $warehouse = Warehouse::create($data);

            if (isset($data['store_ids']) && is_array($data['store_ids'])) {
                $warehouse->stores()->sync($data['store_ids']);
            }

            return $warehouse->load(['stores', 'branch', 'address']);
        });
    }
}
