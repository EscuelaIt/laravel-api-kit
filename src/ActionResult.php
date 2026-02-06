<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit;

class ActionResult
{
    private bool $success;
    private array $errors = [];
    private string $message;
    private array $data = [];

    private function __construct($success, $message = '', $errors = [], $data = [])
    {
        $this->success = $success;
        $this->message = $message;
        $this->errors = $errors;
        $this->data = $data;
    }

    public static function success(string $message = 'Ok', array $data = []): self
    {
        return new self(true, $message, [], $data);
    }

    public static function error(array $errors = [], string $message = 'Error'): self
    {
        $normalizedErrors = [];
        foreach ($errors as $field => $value) {
            $normalizedErrors[$field] = is_string($value) ? [$value] : $value;
        }

        return new self(false, $message, $normalizedErrors);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'errors' => $this->errors,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }
}
