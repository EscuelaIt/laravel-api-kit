<?php

declare(strict_types=1);

namespace EscuelaIT\Test\Fixtures\Actions;

use EscuelaIT\APIKit\ActionResult;
use EscuelaIT\APIKit\CrudAction;

abstract class BaseTestAction extends CrudAction
{
    /**
     * @var array<class-string, callable>
     */
    private static array $handlers = [];

    /**
     * @var array<class-string, array>
     */
    private static array $rules = [];

    public static function setHandler(callable $handler): void
    {
        self::$handlers[static::class] = $handler;
    }

    public static function setRules(array $rules): void
    {
        self::$rules[static::class] = $rules;
    }

    public static function reset(): void
    {
        unset(self::$handlers[static::class], self::$rules[static::class]);
    }

    protected function validationRules(): array
    {
        return self::$rules[static::class] ?? [];
    }

    public function handle(): ActionResult
    {
        $handler = self::$handlers[static::class] ?? null;

        if ($handler) {
            return $handler($this);
        }

        return ActionResult::success('Action executed successfully');
    }
}
