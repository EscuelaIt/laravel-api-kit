<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit\Exceptions;

class UnauthenticatedActionException extends \Exception
{
    public function __construct()
    {
        parent::__construct('This action requires an authenticated user.');
    }
}
