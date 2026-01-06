<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ClientException;
use Exception;

class AccessDeniedException extends ClientException
{
    public function __construct() {
        parent::__construct(
            "ACCESS_DENIED",
            __('exceptions.access_denied'),
            403
        );
    }
}
