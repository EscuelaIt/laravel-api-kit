<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use EscuelaIT\APIKit\ActionResult;
use EscuelaIT\APIKit\ActionService;
use EscuelaIT\APIKit\CrudAction;
use EscuelaIT\APIKit\Exceptions\ActionModelNotDefinedException;
use EscuelaIT\Test\Fixtures\Actions\TestAction;
use EscuelaIT\Test\Fixtures\Actions\TestActionB;
use EscuelaIT\Test\Fixtures\Post;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 *
 * @coversNothing
 */
class ActionServiceTest extends TestCase
{
    #[Test]
    public function itThrowsExceptionWhenActionModelNotDefined(): void
    {
        $this->expectException(ActionModelNotDefinedException::class);

        $service = new ActionService();

        $service->processAction([
            'type' => 'testAction',
            'relatedIds' => [1, 2],
            'data' => [],
        ], null);
    }

    #[Test]
    public function itReturnsTrueWhenActionTypeExists(): void
    {
        $service = new class extends ActionService {
            protected string $actionModel = Post::class;
            protected array $actionTypes = [
                'testAction' => CrudAction::class,
            ];
        };

        $this->assertTrue($service->hasActionType('testAction'));
    }

    #[Test]
    public function itReturnsFalseWhenActionTypeDoesNotExist(): void
    {
        $service = new class extends ActionService {
            protected string $actionModel = Post::class;
            protected array $actionTypes = [
                'testAction' => CrudAction::class,
            ];
        };

        $this->assertFalse($service->hasActionType('nonExistentAction'));
    }

    #[Test]
    public function itProcessesActionSuccessfully(): void
    {
        // Arrange: crear posts
        Post::factory()->create(['id' => 1, 'title' => 'Post 1']);
        Post::factory()->create(['id' => 2, 'title' => 'Post 2']);

        TestAction::setRules([]);
        TestAction::setHandler(static fn (CrudAction $action): ActionResult => ActionResult::success('Action executed successfully'));

        $service = new class extends ActionService {
            protected string $actionModel = Post::class;
            protected array $actionTypes = [];

            public function setActionType(string $name, string $class): void
            {
                $this->actionTypes[$name] = $class;
            }
        };

        $service->setActionType('testAction', TestAction::class);

        // Act
        $result = $service->processAction([
            'type' => 'testAction',
            'relatedIds' => [1, 2],
            'data' => [],
        ], null);

        // Assert
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Action executed successfully', $result->getMessage());
    }

    #[Test]
    public function itFiltersModelsByRelatedIds(): void
    {
        // Arrange: crear posts
        Post::factory()->create(['id' => 1, 'title' => 'Post 1']);
        Post::factory()->create(['id' => 2, 'title' => 'Post 2']);
        Post::factory()->create(['id' => 3, 'title' => 'Post 3']);

        $service = new class extends ActionService {
            protected string $actionModel = Post::class;
            protected array $actionTypes = [];

            public function setActionType(string $name, string $class): void
            {
                $this->actionTypes[$name] = $class;
            }

            public function getQueryCount(): int
            {
                return $this->query->count();
            }
        };

        TestAction::setRules([]);
        TestAction::setHandler(static function (CrudAction $action): ActionResult {
            $count = $action->getModels()->count();

            return ActionResult::success("Processed {$count} models");
        });

        $service->setActionType('testAction', TestAction::class);

        // Act
        $result = $service->processAction([
            'type' => 'testAction',
            'relatedIds' => [1, 3],
            'data' => [],
        ], null);

        // Assert
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Processed 2 models', $result->getMessage());
    }

    #[Test]
    public function itReturnsErrorWhenModelsExceedMaxLimit(): void
    {
        // Arrange: crear muchos posts
        Post::factory()->count(150)->create();

        $service = new class extends ActionService {
            protected string $actionModel = Post::class;
            protected int $maxModelsPerAction = 100;
            protected array $actionTypes = [];

            public function setActionType(string $name, string $class): void
            {
                $this->actionTypes[$name] = $class;
            }
        };

        TestAction::setRules([]);
        TestAction::setHandler(static fn (CrudAction $action): ActionResult => ActionResult::success('Should not be called'));

        $service->setActionType('testAction', TestAction::class);

        // Act
        $relatedIds = range(1, 150);
        $result = $service->processAction([
            'type' => 'testAction',
            'relatedIds' => $relatedIds,
            'data' => [],
        ], null);

        // Assert
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('The number of models to process exceeds the maximum allowed (100).', $result->getMessage());
    }

    #[Test]
    public function itAllowsProcessingWhenModelsAreBelowMaxLimit(): void
    {
        // Arrange: crear posts
        Post::factory()->count(50)->create();

        $service = new class extends ActionService {
            protected string $actionModel = Post::class;
            protected int $maxModelsPerAction = 100;
            protected array $actionTypes = [];

            public function setActionType(string $name, string $class): void
            {
                $this->actionTypes[$name] = $class;
            }
        };

        TestAction::setRules([]);
        TestAction::setHandler(static fn (CrudAction $action): ActionResult => ActionResult::success('Action completed'));

        $service->setActionType('testAction', TestAction::class);

        // Act
        $relatedIds = range(1, 50);
        $result = $service->processAction([
            'type' => 'testAction',
            'relatedIds' => $relatedIds,
            'data' => [],
        ], null);

        // Assert
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Action completed', $result->getMessage());
    }

    #[Test]
    public function itProcessesActionWithCustomMaxModelsPerAction(): void
    {
        // Arrange: crear posts
        Post::factory()->count(10)->create();

        $service = new class extends ActionService {
            protected string $actionModel = Post::class;
            protected int $maxModelsPerAction = 5;
            protected array $actionTypes = [];

            public function setActionType(string $name, string $class): void
            {
                $this->actionTypes[$name] = $class;
            }
        };

        TestAction::setRules([]);
        TestAction::setHandler(static fn (CrudAction $action): ActionResult => ActionResult::success('Should not be called'));

        $service->setActionType('testAction', TestAction::class);

        // Act
        $relatedIds = range(1, 10);
        $result = $service->processAction([
            'type' => 'testAction',
            'relatedIds' => $relatedIds,
            'data' => [],
        ], null);

        // Assert
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('The number of models to process exceeds the maximum allowed (5).', $result->getMessage());
    }

    #[Test]
    public function itPassesUserToActionClass(): void
    {
        // Arrange: crear posts
        Post::factory()->create(['id' => 1, 'title' => 'Post 1']);

        $user = (object) ['id' => 1, 'name' => 'Test User'];

        TestAction::setRules([]);
        TestAction::setHandler(static function (CrudAction $action): ActionResult {
            $user = $action->getUser();
            $userName = $user->name ?? 'No user';

            return ActionResult::success("User: {$userName}");
        });

        $service = new class extends ActionService {
            protected string $actionModel = Post::class;
            protected array $actionTypes = [];

            public function setActionType(string $name, string $class): void
            {
                $this->actionTypes[$name] = $class;
            }
        };

        $service->setActionType('testAction', TestAction::class);

        // Act
        $result = $service->processAction([
            'type' => 'testAction',
            'relatedIds' => [1],
            'data' => [],
        ], $user);

        // Assert
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('User: Test User', $result->getMessage());
    }

    #[Test]
    public function itPassesDataToActionClass(): void
    {
        // Arrange: crear posts
        Post::factory()->create(['id' => 1, 'title' => 'Post 1']);

        $actionData = ['status' => 'published', 'priority' => 'high'];

        TestAction::setRules([]);
        TestAction::setHandler(static function (CrudAction $action): ActionResult {
            $data = $action->getData();
            $status = $data['status'] ?? 'unknown';

            return ActionResult::success("Status: {$status}");
        });

        $service = new class extends ActionService {
            protected string $actionModel = Post::class;
            protected array $actionTypes = [];

            public function setActionType(string $name, string $class): void
            {
                $this->actionTypes[$name] = $class;
            }
        };

        $service->setActionType('testAction', TestAction::class);

        // Act
        $result = $service->processAction([
            'type' => 'testAction',
            'relatedIds' => [1],
            'data' => $actionData,
        ], null);

        // Assert
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Status: published', $result->getMessage());
    }

    #[Test]
    public function itUsesCustomIdentifierField(): void
    {
        // Arrange: crear posts con IDs específicos
        Post::factory()->create(['id' => 10, 'title' => 'Post A']);
        Post::factory()->create(['id' => 20, 'title' => 'Post B']);
        Post::factory()->create(['id' => 30, 'title' => 'Post C']);

        $service = new class extends ActionService {
            protected string $actionModel = Post::class;
            protected string $identifierField = 'id';
            protected array $actionTypes = [];

            public function setActionType(string $name, string $class): void
            {
                $this->actionTypes[$name] = $class;
            }
        };

        TestAction::setRules([]);
        TestAction::setHandler(static function (CrudAction $action): ActionResult {
            $count = $action->getModels()->count();

            return ActionResult::success("Found {$count} models");
        });

        $service->setActionType('testAction', TestAction::class);

        // Act
        $result = $service->processAction([
            'type' => 'testAction',
            'relatedIds' => [10, 30],
            'data' => [],
        ], null);

        // Assert
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Found 2 models', $result->getMessage());
    }

    #[Test]
    public function itProcessesActionWithEmptyRelatedIds(): void
    {
        // Arrange: crear posts
        Post::factory()->count(5)->create();

        $service = new class extends ActionService {
            protected string $actionModel = Post::class;
            protected array $actionTypes = [];

            public function setActionType(string $name, string $class): void
            {
                $this->actionTypes[$name] = $class;
            }
        };

        TestAction::setRules([]);
        TestAction::setHandler(static function (CrudAction $action): ActionResult {
            $count = $action->getModels()->count();

            return ActionResult::success("Processed {$count} models");
        });

        $service->setActionType('testAction', TestAction::class);

        // Act
        $result = $service->processAction([
            'type' => 'testAction',
            'relatedIds' => [],
            'data' => [],
        ], null);

        // Assert
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Processed 0 models', $result->getMessage());
    }

    #[Test]
    public function itProcessesActionWithNonExistentRelatedIds(): void
    {
        // Arrange: crear posts con IDs 1, 2, 3
        Post::factory()->create(['id' => 1, 'title' => 'Post 1']);
        Post::factory()->create(['id' => 2, 'title' => 'Post 2']);
        Post::factory()->create(['id' => 3, 'title' => 'Post 3']);

        $service = new class extends ActionService {
            protected string $actionModel = Post::class;
            protected array $actionTypes = [];

            public function setActionType(string $name, string $class): void
            {
                $this->actionTypes[$name] = $class;
            }
        };

        TestAction::setRules([]);
        TestAction::setHandler(static function (CrudAction $action): ActionResult {
            $count = $action->getModels()->count();

            return ActionResult::success("Found {$count} models");
        });

        $service->setActionType('testAction', TestAction::class);

        // Act: intentar procesar IDs que no existen
        $result = $service->processAction([
            'type' => 'testAction',
            'relatedIds' => [99, 100],
            'data' => [],
        ], null);

        // Assert
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Found 0 models', $result->getMessage());
    }

    #[Test]
    public function itProcessesMultipleActionTypes(): void
    {
        // Arrange: crear posts
        Post::factory()->create(['id' => 1, 'title' => 'Post 1']);

        TestAction::setRules([]);
        TestAction::setHandler(static fn (CrudAction $action): ActionResult => ActionResult::success('Action A executed'));

        TestActionB::setRules([]);
        TestActionB::setHandler(static fn (CrudAction $action): ActionResult => ActionResult::success('Action B executed'));

        $service = new class extends ActionService {
            protected string $actionModel = Post::class;
            protected array $actionTypes = [];

            public function setActionType(string $name, string $class): void
            {
                $this->actionTypes[$name] = $class;
            }
        };

        $service->setActionType('actionA', TestAction::class);
        $service->setActionType('actionB', TestActionB::class);

        // Act & Assert: ejecutar acción A
        $resultA = $service->processAction([
            'type' => 'actionA',
            'relatedIds' => [1],
            'data' => [],
        ], null);

        $this->assertTrue($resultA->isSuccess());
        $this->assertEquals('Action A executed', $resultA->getMessage());

        // Act & Assert: ejecutar acción B
        $resultB = $service->processAction([
            'type' => 'actionB',
            'relatedIds' => [1],
            'data' => [],
        ], null);

        $this->assertTrue($resultB->isSuccess());
        $this->assertEquals('Action B executed', $resultB->getMessage());
    }

    #[Test]
    public function itReturnsErrorWhenActionValidationFails(): void
    {
        // Arrange: crear posts
        Post::factory()->create(['id' => 1, 'title' => 'Post 1']);

        TestAction::setRules([
            'title' => 'required|string',
        ]);
        TestAction::setHandler(static fn (CrudAction $action): ActionResult => ActionResult::success('Should not be called'));

        $service = new class extends ActionService {
            protected string $actionModel = Post::class;
            protected array $actionTypes = [];

            public function setActionType(string $name, string $class): void
            {
                $this->actionTypes[$name] = $class;
            }
        };

        $service->setActionType('testAction', TestAction::class);

        // Act
        $result = $service->processAction([
            'type' => 'testAction',
            'relatedIds' => [1],
            'data' => [], // Sin título (requerido)
        ], null);

        // Assert
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('The provided data is not valid.', $result->getMessage());
        $this->assertArrayHasKey('title', $result->getErrors());
    }

    #[Test]
    public function itProcessesActionWithValidData(): void
    {
        // Arrange: crear posts
        Post::factory()->create(['id' => 1, 'title' => 'Post 1']);

        TestAction::setRules([
            'title' => 'required|string',
        ]);
        TestAction::setHandler(static function (CrudAction $action): ActionResult {
            $data = $action->getData();
            $title = $data['title'];

            return ActionResult::success("Updated to: {$title}");
        });

        $service = new class extends ActionService {
            protected string $actionModel = Post::class;
            protected array $actionTypes = [];

            public function setActionType(string $name, string $class): void
            {
                $this->actionTypes[$name] = $class;
            }
        };

        $service->setActionType('testAction', TestAction::class);

        // Act
        $result = $service->processAction([
            'type' => 'testAction',
            'relatedIds' => [1],
            'data' => ['title' => 'New Title'],
        ], null);

        // Assert
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Updated to: New Title', $result->getMessage());
    }
}
