<?php

namespace App\Exceptions\Stock;

use App\Exceptions\ClientException;
use Illuminate\Http\Response;

class InsufficientStockException extends ClientException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(
            'INSUFFICIENT_STOCK',
            $message ?? __('exceptions.insufficient_stock'),
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }
}
