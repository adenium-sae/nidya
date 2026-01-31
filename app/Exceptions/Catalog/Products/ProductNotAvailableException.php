<?php

namespace App\Exceptions\Catalog\Products;

use App\Exceptions\ClientException;
use Illuminate\Http\Response;

class ProductNotAvailableException extends ClientException
{
    public function __construct(string $productName)
    {
        parent::__construct(
            'PRODUCT_NOT_AVAILABLE',
            __('exceptions.product_not_available', ['product' => $productName]),
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }
}
