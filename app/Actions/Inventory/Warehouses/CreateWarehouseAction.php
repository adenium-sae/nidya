<?php

namespace App\Actions\Inventory\Warehouses;

use App\Models\Warehouse;

class CreateWarehouseAction
{
    public function __invoke(array $data): Warehouse
    {
        return Warehouse::create($data);
    }
}
