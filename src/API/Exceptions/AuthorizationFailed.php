<?php

namespace Tsukaeru\RushFiles\API\Exceptions;

use Exception;

class AuthorizationFailed extends Exception
{
    public function __construct(string $message = "" , int $code = 0 , $previous = null)
    {
        //var_dump($previous);
        parent::__construct($message, $code, $previous);
    }
}