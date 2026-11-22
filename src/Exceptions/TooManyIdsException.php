<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit\Exceptions;

use Illuminate\Http\JsonResponse;
use Negartarh\APIWrapper\Facades\APIResponse;

/**
 * Thrown by ListService::getAllIds() when the query matches more items than $maxIds,
 * so clients never get a truncated list of identifiers without knowing it.
 */
class TooManyIdsException extends \Exception
{
    public function __construct(public readonly int $count, public readonly int $maxIds)
    {
        parent::__construct(__('The number of items (:count) exceeds the maximum allowed (:max).', [
            'count' => $count,
            'max' => $maxIds,
        ]));
    }

    public function render(): JsonResponse
    {
        return new JsonResponse(APIResponse::wrap([
            'count' => $this->count,
            'maxIds' => $this->maxIds,
        ], 422, $this->getMessage()), 422);
    }
}
