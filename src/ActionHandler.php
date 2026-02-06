<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Negartarh\APIWrapper\Facades\APIResponse;

trait ActionHandler
{
    public function handleAction(ActionService $actionService) {
        $user = Auth::user();

        $validator = Validator::make(request()->all(), [
            'type' => ['required', 'string', 'max:250'],
            'relatedIds' => ['required', 'array'],
            'data' => ['present'],
        ]);

        if($validator->fails()) {
            return APIResponse::unprocessableEntity($validator->errors());
        }

        if(! $actionService->hasActionType(request()->type)) {
            return APIResponse::unprocessableEntity([], 'The action type is not valid.');
        }

        $response = $actionService->processAction($validator->validated(), $user);
        if ($response->isSuccess()) {
            return APIResponse::ok($response->getData(), $response->getMessage());
        }
        return APIResponse::unprocessableEntity($response->getErrors(), $response->getMessage());
    }
}
