<?php

namespace App\Exceptions\Users;

use App\Exceptions\ClientException;
use Exception;

class InvalidOtpCodeException extends ClientException
{
    public function __construct() {
        parent::__construct(
            "INVALID_OTP_CODE",
            "The provided OTP code is invalid or has expired.",
            401
        );
    }
}
