<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ClientException;

class OtpInvalidException extends ClientException
{
    public function __construct() {
        parent::__construct(
            "OTP_INVALID",
            __('exceptions.otp_invalid'),
            401
        );
    }
}
