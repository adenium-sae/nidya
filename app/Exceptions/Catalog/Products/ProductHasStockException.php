<?php

namespace App\Exceptions\Catalog\Products;

use App\Exceptions\ClientException;

class ProductHasStockException extends ClientException
{
    public function __construct() {
        parent::__construct(
            "PRODUCT_HAS_STOCK",
            __('exceptions.product_has_stock'),
            422
        );
    }
}
