<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit;

use Illuminate\Notifications\Action;
use Illuminate\Support\Facades\Validator;

abstract class CrudAction
{

    protected $models;
    protected $data;
    protected $user;
    protected $validationErrors;

    public function __construct($models, $data, $user) {
        $this->models = $models;
        $this->data = $data;
        $this->user = $user;
    }

    abstract public function handle(): ActionResult;

    public function processAction(): ActionResult
    {
        if( $this->isValidData() ) {
            return $this->handle();
        } else {
            return ActionResult::error(
                $this->validationErrors ?? [],
                'The provided data is not valid.'
            );
        }
    }

    protected function validationRules(): array {
        return [];
    }

    protected function isValidData(): bool {
        $validator = Validator::make($this->data, $this->validationRules());
        $this->validationErrors = $validator->errors()->toArray();
        return ! $validator->fails();
    }

    public function getModels() {
        return $this->models;
    }

    public function getData() {
        return $this->data;
    }

    public function getUser() {
        return $this->user;
    }

    protected function createActionResultSuccess(string $message, array $data = []): ActionResult {
        return ActionResult::success($message, [
                'msg' => $message,
                'action' => class_basename(static::class),
                'data' => $data,
        ]);
    }

    protected function createActionResultError(string $message = "Unprocessable action", array $errors = []): ActionResult {
        return ActionResult::error($errors, $message);
    }
}
