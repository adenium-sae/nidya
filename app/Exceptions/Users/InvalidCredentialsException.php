<?php

namespace App\Exceptions\Users;

use App\Exceptions\ClientException;
use Exception;

class InvalidCredentialsException extends ClientException
{
    public function __construct() {
        parent::__construct(
            "INVALID_CREDENTIALS",
            __('exceptions.invalid_credentials'),
            401
        );
    }
}
