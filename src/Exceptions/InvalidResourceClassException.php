<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit\Exceptions;

use Illuminate\Http\Resources\Json\JsonResource;

class InvalidResourceClassException extends \Exception
{
    public function __construct(string $resourceClass)
    {
        $message = "The '{$resourceClass}' class must extend ".JsonResource::class.'. '
                 .'Make sure your resource class extends Illuminate\Http\Resources\Json\JsonResource.';

        parent::__construct($message);
    }
}
