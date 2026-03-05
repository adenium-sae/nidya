<?php

namespace App\Exceptions\Access\Auth;

use App\Exceptions\ClientException;

class UserInactiveException extends ClientException
{
    public function __construct() {
        parent::__construct(
            "USER_INACTIVE",
            __('exceptions.user_inactive'),
            403
        );
    }
}
