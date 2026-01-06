<?php

namespace App\Exceptions\Users;

use App\Exceptions\ClientException;

class EmailAlreadyTakenException extends ClientException
{
    public function __construct() {
        parent::__construct(
            "EMAIL_ALREADY_TAKEN",
            __('exceptions.email_already_taken'),
            409
        );
    }
}
