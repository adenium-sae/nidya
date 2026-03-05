<?php

namespace App\Exceptions\Sales;

use App\Exceptions\ClientException;
use Illuminate\Http\Response;

class SaleCancellationException extends ClientException
{
    public function __construct(string $message)
    {
        parent::__construct(
            'SALE_CANCELLATION_ERROR',
            $message,
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }
}
