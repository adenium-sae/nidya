<?php

namespace App\Actions\Inventory\Warehouses;

use App\Exceptions\Inventory\Warehouses\WarehouseNotFoundException;
use App\Models\Warehouse;

use Illuminate\Support\Facades\DB;

class UpdateWarehouseAction
{
    public function __invoke(string $id, array $data): Warehouse
    {
        return DB::transaction(function () use ($id, $data) {
            /** @var Warehouse|null $warehouse */
            $warehouse = Warehouse::find($id);
            if (!$warehouse) {
                throw new WarehouseNotFoundException();
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
