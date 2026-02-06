<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use Illuminate\Http\Request;
use EscuelaIT\APIKit\ActionHandler;
use EscuelaIT\APIKit\ActionService;
use PHPUnit\Framework\Attributes\Test;
use Negartarh\APIWrapper\Facades\APIResponse;

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
  public function itCallsHasActionTypeWhenValidationPasses(): void
  {
    $request = Request::create('/', 'POST', [
      'type' => 'test-action',
      'relatedIds' => [1, 2, 3],
      'data' => ['foo' => 'bar']
    ]);
    $this->app->instance('request', $request);

    $service = \Mockery::mock(ActionService::class);
    $service->shouldReceive('hasActionType')
      ->once()
      ->with('test-action')
      ->andReturn(true);
    
    $actionResult = \Mockery::mock(\EscuelaIT\APIKit\ActionResult::class);
    $actionResult->shouldReceive('isSuccess')
      ->once()
      ->andReturn(true);
    $actionResult->shouldReceive('toArray')
      ->once()
      ->andReturn([]);
    
    $service->shouldReceive('processAction')
      ->once()
      ->andReturn($actionResult);

    APIResponse::shouldReceive('ok')
      ->once()
      ->with([]);

    $this->actionController->handleAction($service);
  }
}