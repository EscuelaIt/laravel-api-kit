<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit\Exceptions;

class MissingRequiredScopeException extends \Exception
{
    public function __construct(string $scopeName)
    {
        parent::__construct("Missing required scope: {$scopeName}");
    }
}
