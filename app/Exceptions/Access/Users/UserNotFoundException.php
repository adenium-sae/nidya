<?php

namespace App\Exceptions\Access\Users;

use App\Exceptions\ClientException;

class UserNotFoundException extends ClientException
{
    public function __construct() {
        parent::__construct(
            "USER_NOT_FOUND",
            __('exceptions.user_not_found'),
            404
        );
    }
}
