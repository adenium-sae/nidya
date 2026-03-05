<?php

namespace App\Actions\Inventory\Warehouses;

use App\Exceptions\Inventory\Warehouses\WarehouseNotFoundException;
use App\Models\Warehouse;

class DeleteWarehouseAction
{
    public function __invoke(string $id): void
    {
        /** @var Warehouse|null $warehouse */
        $warehouse = Warehouse::find($id);
        if (!$warehouse) {
            throw new WarehouseNotFoundException();
        }
        $warehouse->delete();
    }
}
