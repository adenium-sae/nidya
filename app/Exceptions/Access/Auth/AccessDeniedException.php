<?php

namespace App\Exceptions\Access\Auth;

use App\Exceptions\ClientException;
use Exception;

class AccessDeniedException extends ClientException
{
    public function __construct($message) {
        parent::__construct(
            "ACCESS_DENIED",
            __($message ?: 'exceptions.access_denied'),
            403
        );
    }
}
