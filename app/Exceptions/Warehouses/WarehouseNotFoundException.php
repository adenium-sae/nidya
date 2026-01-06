<?php

namespace App\Exceptions\Warehouses;

use App\Exceptions\ClientException;
use Exception;

class WarehouseNotFoundException extends ClientException
{
    public function __construct() {
        parent::__construct(
            "WAREHOUSE_NOT_FOUND",
            "The specified warehouse could not be found.",
            404
        );
    }
}
