<?php

namespace App\Exceptions\Branches;

use App\Exceptions\ClientException;

class BranchNotFoundException extends ClientException
{
    public function __construct() {
        parent::__construct(
            "BRANCH_NOT_FOUND",
            __('exceptions.branch_not_found'),
            404
        );
    }
}