<?php

namespace App\Exceptions\Inventory\Warehouses;

use App\Exceptions\ClientException;

class WarehouseNotFoundException extends ClientException
{
    public function __construct() {
        parent::__construct(
            "WAREHOUSE_NOT_FOUND",
            __('exceptions.warehouse_not_found'),
            404
        );
    }
}
