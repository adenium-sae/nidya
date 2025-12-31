<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ClientException;
use Exception;

class AccessDeniedException extends ClientException
{
    public function __construct() {
        parent::__construct(
            "ACCESS_DENIED",
            "You do not have permission to access this resource.",
            403
        );
    }
}
