<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use EscuelaIT\APIKit\ListService;
use EscuelaIT\APIKit\ResourceListable;
use Illuminate\Http\Request;
use Negartarh\APIWrapper\Facades\APIResponse;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 *
 * @coversNothing
 */
class ResourceListableTest extends TestCase
{
    public $resourceControllerClass;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resourceControllerClass = new class {
            use ResourceListable;
        };
    }

    #[Test]
    public function itReturnValidationErrorsWithInvalidQueryStringParams(): void
    {
        $request = Request::create('/?per_page=abc', 'GET');
        $this->app->instance('request', $request);

        $service = \Mockery::mock(ListService::class);
        $service->shouldNotReceive('setSearchConfiguration');

        APIResponse::shouldReceive('unprocessableEntity');

        $this->resourceControllerClass->list($service);
    }

    #[Test]
    public function itReturnsOkWithValidQueryStringParams(): void
    {
        $request = Request::create(
            '/?per_page=25&sortField=id&sortDirection=desc&keyword=foo&filters[0][name]=status&filters[0][value]=published&filters[0][active]=true',
            'GET'
        );
        $this->app->instance('request', $request);

        $expectedConfig = [
            'perPage' => '25',
            'sortField' => 'id',
            'sortDirection' => 'desc',
            'keyword' => 'foo',
            'filters' => [
                [
                    'name' => 'status',
                    'value' => 'published',
                    'active' => 'true',
                ],
            ],
            'belongsTo' => null,
            'relationId' => null,
            'include' => null,
        ];

        $service = \Mockery::mock(ListService::class);
        $service->shouldReceive('setSearchConfiguration')
            ->once()
            ->with($expectedConfig)
            ->andReturnSelf()
        ;

        $service->shouldReceive('getResults')
            ->once()
            ->andReturn([
                'countItems' => 2,
                'result' => ['a', 'b'],
            ])
        ;

        APIResponse::shouldReceive('ok')
            ->once()
            ->with([
                'countItems' => 2,
                'result' => ['a', 'b'],
            ], '2 items found')
        ;

        $this->resourceControllerClass->list($service);
    }

    #[Test]
    public function itReturnsOkWithNoQueryStringParams(): void
    {
        $request = Request::create('/', 'GET');
        $this->app->instance('request', $request);

        $expectedConfig = [
            'perPage' => null,
            'sortField' => null,
            'sortDirection' => null,
            'keyword' => null,
            'filters' => null,
            'belongsTo' => null,
            'relationId' => null,
            'include' => null,
        ];

        $service = \Mockery::mock(ListService::class);
        $service->shouldReceive('setSearchConfiguration')
            ->once()
            ->with($expectedConfig)
            ->andReturnSelf()
        ;

        $service->shouldReceive('getResults')
            ->once()
            ->andReturn(['a', 'b', 'c'])
        ;

        APIResponse::shouldReceive('ok')
            ->once()
            ->with(['a', 'b', 'c'], '3 items found')
        ;

        $this->resourceControllerClass->list($service);
    }
}
