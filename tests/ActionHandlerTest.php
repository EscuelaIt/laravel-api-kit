<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use EscuelaIT\APIKit\ActionHandler;
use EscuelaIT\APIKit\ActionResult;
use EscuelaIT\APIKit\ActionService;
use Illuminate\Http\Request;
use Negartarh\APIWrapper\Facades\APIResponse;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 *
 * @coversNothing
 */
class ActionHandlerTest extends TestCase
{
    public $actionController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actionController = new class {
            use ActionHandler;
        };
    }

    #[Test]
    public function itReturnValidationErrorsWithInvalidQueryStringParams(): void
    {
        $request = Request::create('/', 'POST', []);
        $this->app->instance('request', $request);

        $service = \Mockery::mock(ActionService::class);
        $service->shouldNotReceive(methodNames: 'hasActionType');
        $service->shouldNotReceive('processAction');

        APIResponse::shouldReceive('unprocessableEntity');

        $this->actionController->handleAction($service);
    }

    #[Test]
    public function itProcessActionWhenValidationPasses(): void
    {
        $request = Request::create('/', 'POST', [
            'type' => 'test-action',
            'relatedIds' => [1, 2, 3],
            'data' => ['foo' => 'bar'],
        ]);
        $this->app->instance('request', $request);

        $service = \Mockery::mock(ActionService::class);
        $service->shouldReceive('hasActionType')
            ->once()
            ->with('test-action')
            ->andReturn(true)
        ;

        $actionResult = \Mockery::mock(ActionResult::class);
        $actionResult->shouldReceive('isSuccess')
            ->once()
            ->andReturn(true)
        ;
        $actionResult->shouldReceive('getData')
            ->once()
            ->andReturn([])
        ;
        $actionResult->shouldReceive('getMessage')
            ->once()
            ->andReturn('')
        ;

        $service->shouldReceive('processAction')
            ->once()
            ->andReturn($actionResult)
        ;

        APIResponse::shouldReceive('ok')
            ->once()
            ->with([], '')
        ;

        $this->actionController->handleAction($service);
    }
}
