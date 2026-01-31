<?php

namespace App\Exceptions\Inventory\Warehouses;

use App\Exceptions\ClientException;

class GlobalWarehouseMissingStoreException extends ClientException
{
    public function __construct() {
        parent::__construct(
            "GLOBAL_WAREHOUSE_MISSING_STORE",
            __('exceptions.global_warehouse_missing_store'),
            400
        );
    }
}
