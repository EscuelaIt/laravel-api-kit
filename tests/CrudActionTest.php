<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use EscuelaIT\APIKit\ActionResult;
use EscuelaIT\APIKit\CrudAction;

/**
 * @internal
 *
 * @coversNothing
 */
class CrudActionTest extends TestCase
{
    public function testCrudActionReturnsSuccessWhenDataIsValid(): void
    {
        $action = new class([], ['name' => 'Test'], null) extends CrudAction {
            protected function validationRules(): array
            {
                return [
                    'name' => 'required|string',
                ];
            }

            public function handle(): ActionResult
            {
                return ActionResult::success('Action completed successfully');
            }
        };

        $result = $action->processAction();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Action completed successfully', $result->getMessage());
        $this->assertEmpty($result->getErrors());
    }

    public function testCrudActionReturnsErrorWhenDataIsInvalid(): void
    {
        $action = new class([], ['name' => ''], null) extends CrudAction {
            protected function validationRules(): array
            {
                return [
                    'name' => 'required|string',
                ];
            }

            public function handle(): ActionResult
            {
                return ActionResult::success('This should not be called');
            }
        };

        $result = $action->processAction();

        $this->assertFalse($result->isSuccess());
        $this->assertNotEmpty($result->getErrors());
        $this->assertArrayHasKey('name', $result->getErrors());
    }

    public function testCrudActionValidatesMultipleFields(): void
    {
        $action = new class([], ['email' => 'invalid-email', 'age' => -5], null) extends CrudAction {
            protected function validationRules(): array
            {
                return [
                    'email' => 'required|email',
                    'age' => 'required|integer|min:0',
                ];
            }

            public function handle(): ActionResult
            {
                return ActionResult::success('This should not be called');
            }
        };

        $result = $action->processAction();

        $this->assertFalse($result->isSuccess());
        $this->assertArrayHasKey('email', $result->getErrors());
        $this->assertArrayHasKey('age', $result->getErrors());
    }

    public function testCrudActionStoresModelsDataAndUser(): void
    {
        $models = ['User' => 'UserModel'];
        $data = ['name' => 'John Doe'];
        $user = (object) ['id' => 1, 'name' => 'Admin'];

        $action = new class($models, $data, $user) extends CrudAction {
            protected function validationRules(): array
            {
                return [
                    'name' => 'required',
                ];
            }

            public function handle(): ActionResult
            {
                return ActionResult::success('Valid');
            }
        };

        $this->assertEquals($models, $action->getModels());
        $this->assertEquals($data, $action->getData());
        $this->assertEquals($user, $action->getUser());
    }

    public function testCrudActionWithEmptyValidationRules(): void
    {
        $action = new class([], [], null) extends CrudAction {
            public function handle(): ActionResult
            {
                return ActionResult::success('No validation needed');
            }
        };

        $result = $action->processAction();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('No validation needed', $result->getMessage());
    }

    public function testCrudActionValidatesWithCustomMessages(): void
    {
        $action = new class([], ['phone' => ''], null) extends CrudAction {
            protected function validationRules(): array
            {
                return [
                    'phone' => 'required|numeric',
                ];
            }

            public function handle(): ActionResult
            {
                return ActionResult::success('Should not be called');
            }
        };

        $result = $action->processAction();

        $this->assertFalse($result->isSuccess());
        $this->assertArrayHasKey('phone', $result->getErrors());
    }

    public function testCrudActionWithNestedValidationRules(): void
    {
        $action = new class([], ['user' => ['name' => 'John', 'email' => 'john@example.com']], null) extends CrudAction {
            protected function validationRules(): array
            {
                return [
                    'user.name' => 'required|string',
                    'user.email' => 'required|email',
                ];
            }

            public function handle(): ActionResult
            {
                return ActionResult::success('Valid nested data');
            }
        };

        $result = $action->processAction();

        $this->assertTrue($result->isSuccess());
    }

    public function testCrudActionFailsNestedValidation(): void
    {
        $action = new class([], ['user' => ['name' => '', 'email' => 'invalid-email']], null) extends CrudAction {
            protected function validationRules(): array
            {
                return [
                    'user.name' => 'required|string',
                    'user.email' => 'required|email',
                ];
            }

            public function handle(): ActionResult
            {
                return ActionResult::success('Should not reach here');
            }
        };

        $result = $action->processAction();

        $this->assertFalse($result->isSuccess());
        $this->assertNotEmpty($result->getErrors());
    }
}
