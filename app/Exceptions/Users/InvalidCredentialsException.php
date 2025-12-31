<?php

namespace App\Exceptions\Users;

use App\Exceptions\ClientException;
use Exception;

class InvalidCredentialsException extends ClientException
{
    public function __construct() {
        parent::__construct(
            "INVALID_CREDENTIALS",
            "The provided credentials are invalid.",
            401
        );
    }
}
