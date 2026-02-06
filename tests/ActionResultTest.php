<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use EscuelaIT\APIKit\ActionResult;

/**
 * @internal
 *
 * @coversNothing
 */
class ActionResultTest extends TestCase
{
    public function testActionResultSuccessReturnsTrue(): void
    {
        $result = ActionResult::success('Operation completed');

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Operation completed', $result->getMessage());
        $this->assertEmpty($result->getErrors());
        $this->assertEmpty($result->getData());
    }

    public function testActionResultSuccessWithDefaultMessage(): void
    {
        $result = ActionResult::success();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Ok', $result->getMessage());
        $this->assertEmpty($result->getErrors());
        $this->assertEmpty($result->getData());
    }

    public function testActionResultErrorReturnsFalse(): void
    {
        $errors = ['email' => ['Email is required']];
        $result = ActionResult::error($errors, 'Validation failed');

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Validation failed', $result->getMessage());
        $this->assertEquals($errors, $result->getErrors());
    }

    public function testActionResultErrorWithDefaultMessage(): void
    {
        $errors = ['name' => ['Name is required']];
        $result = ActionResult::error($errors);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Error', $result->getMessage());
        $this->assertEquals($errors, $result->getErrors());
    }

    public function testActionResultErrorWithEmptyErrors(): void
    {
        $result = ActionResult::error([], 'Something went wrong');

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Something went wrong', $result->getMessage());
        $this->assertEmpty($result->getErrors());
    }

    public function testActionResultReturnsToArrayFormatSuccess(): void
    {
        $result = ActionResult::success('Success message');
        $resultArray = $result->toArray();

        $this->assertIsArray($resultArray);
        $this->assertArrayHasKey('success', $resultArray);
        $this->assertArrayHasKey('message', $resultArray);
        $this->assertArrayHasKey('errors', $resultArray);
        $this->assertArrayHasKey('data', $resultArray);
        $this->assertTrue($resultArray['success']);
        $this->assertEquals('Success message', $resultArray['message']);
        $this->assertEmpty($resultArray['errors']);
        $this->assertEmpty($resultArray['data']);
    }

    public function testActionResultReturnsToArrayFormatError(): void
    {
        $errors = ['email' => ['Invalid email'], 'phone' => ['Phone is required']];
        $result = ActionResult::error($errors, 'Validation error');
        $resultArray = $result->toArray();

        $this->assertIsArray($resultArray);
        $this->assertArrayHasKey('success', $resultArray);
        $this->assertArrayHasKey('message', $resultArray);
        $this->assertArrayHasKey('errors', $resultArray);
        $this->assertArrayHasKey('data', $resultArray);
        $this->assertFalse($resultArray['success']);
        $this->assertEquals('Validation error', $resultArray['message']);
        $this->assertEquals($errors, $resultArray['errors']);
        $this->assertEmpty($resultArray['data']);
    }

    public function testActionResultWithMultipleErrors(): void
    {
        $errors = [
            'email' => ['Email is required', 'Email must be valid'],
            'password' => ['Password must be at least 8 characters'],
            'age' => ['Age must be between 18 and 65'],
        ];
        $result = ActionResult::error($errors);

        $this->assertFalse($result->isSuccess());
        $this->assertCount(3, $result->getErrors());
        $this->assertArrayHasKey('email', $result->getErrors());
        $this->assertArrayHasKey('password', $result->getErrors());
        $this->assertArrayHasKey('age', $result->getErrors());
    }

    public function testActionResultGetErrorsReturnsArray(): void
    {
        $errors = ['field1' => ['error1'], 'field2' => ['error2']];
        $result = ActionResult::error($errors);

        $returnedErrors = $result->getErrors();
        $this->assertIsArray($returnedErrors);
        $this->assertEquals($errors, $returnedErrors);
    }

    public function testActionResultGetMessageReturnsString(): void
    {
        $message = 'Custom error message';
        $result = ActionResult::error([], $message);

        $this->assertIsString($result->getMessage());
        $this->assertEquals($message, $result->getMessage());
    }

    public function testActionResultIsSuccessReturnsBoolean(): void
    {
        $successResult = ActionResult::success();
        $errorResult = ActionResult::error();

        $this->assertIsBool($successResult->isSuccess());
        $this->assertIsBool($errorResult->isSuccess());
        $this->assertTrue($successResult->isSuccess());
        $this->assertFalse($errorResult->isSuccess());
    }

    public function testActionResultSuccessWithData(): void
    {
        $data = ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'];
        $result = ActionResult::success('User created', $data);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('User created', $result->getMessage());
        $this->assertEquals($data, $result->getData());
        $this->assertEmpty($result->getErrors());
    }

    public function testActionResultSuccessWithComplexData(): void
    {
        $data = [
            'user' => ['id' => 1, 'name' => 'John'],
            'permissions' => ['read', 'write', 'delete'],
            'metadata' => ['created_at' => '2026-01-30', 'updated_at' => '2026-01-30'],
        ];
        $result = ActionResult::success('Data retrieved', $data);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($data, $result->getData());
        $this->assertArrayHasKey('user', $result->getData());
        $this->assertArrayHasKey('permissions', $result->getData());
        $this->assertArrayHasKey('metadata', $result->getData());
    }

    public function testActionResultSuccessDataInToArray(): void
    {
        $data = ['items' => [1, 2, 3], 'total' => 3];
        $result = ActionResult::success('Items fetched', $data);
        $resultArray = $result->toArray();

        $this->assertArrayHasKey('data', $resultArray);
        $this->assertEquals($data, $resultArray['data']);
        $this->assertTrue($resultArray['success']);
    }

    public function testActionResultGetDataReturnsArray(): void
    {
        $data = ['key' => 'value'];
        $result = ActionResult::success('Success', $data);

        $this->assertIsArray($result->getData());
        $this->assertEquals($data, $result->getData());
    }
}
