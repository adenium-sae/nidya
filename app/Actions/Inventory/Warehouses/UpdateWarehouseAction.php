<?php

namespace App\Actions\Inventory\Warehouses;

use App\Exceptions\Inventory\Warehouses\WarehouseNotFoundException;
use App\Models\Warehouse;

class UpdateWarehouseAction
{
    public function __invoke(string $id, array $data): Warehouse
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
}
