<?php

namespace App\Exceptions\Users;

use App\Exceptions\ClientException;
use Exception;

class InvalidOtpCodeException extends ClientException
{
    public function __construct() {
        parent::__construct(
            "INVALID_OTP_CODE",
            __('exceptions.invalid_otp_code'),
            401
        );
    }
}
