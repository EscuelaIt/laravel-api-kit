<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit\Exceptions;

use Illuminate\Http\JsonResponse;
use Negartarh\APIWrapper\Facades\APIResponse;

class MissingRequiredScopeException extends \Exception
{
    public function __construct(string $scopeName)
    {
        parent::__construct("Missing required scope: {$scopeName}");
    }

    public function render(): JsonResponse
    {
        return new JsonResponse(APIResponse::wrap([], 403, $this->getMessage()), 403);
    }
}
