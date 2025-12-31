<?php

namespace App\Exceptions;

use Exception;

class ClientException extends Exception
{
    public string $keyCode;
    public int $status;

    public function __construct(string $keyCode, string $message, int $status)
    {
        parent::__construct($message);
        $this->keyCode = $keyCode;
        $this->status = $status;
    }
}
