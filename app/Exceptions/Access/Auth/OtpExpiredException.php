<?php

namespace App\Exceptions\Access\Auth;

use App\Exceptions\ClientException;

class OtpExpiredException extends ClientException
{
    public function __construct() {
        parent::__construct(
            "OTP_EXPIRED",
            __('exceptions.otp_expired'),
            401
        );
    }
}
