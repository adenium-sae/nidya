<?php

namespace App\Exceptions\Catalog\Products;

use App\Exceptions\ClientException;

class ProductNotFoundException extends ClientException
{
    public function __construct() {
        parent::__construct(
            "PRODUCT_NOT_FOUND",
            __('exceptions.product_not_found'),
            404
        );
    }
}
