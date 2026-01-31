<?php

namespace App\Exceptions\Access\Auth;

use App\Exceptions\ClientException;

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
