<?php

namespace App\Exceptions\Organization\Stores;

use App\Exceptions\ClientException;

class StoreNotFoundException extends ClientException
{
    public function __construct() {
        parent::__construct(
            "STORE_NOT_FOUND",
            __('exceptions.store_not_found'),
            404
        );
    }
}