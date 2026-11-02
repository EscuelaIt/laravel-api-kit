<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use EscuelaIT\APIKit\ListService;
use EscuelaIT\APIKit\ResourceListable;
use EscuelaIT\Test\Fixtures\LocalizedPostResource;
use EscuelaIT\Test\Fixtures\Post;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Negartarh\APIWrapper\Facades\APIResponse;
use PHPUnit\Framework\Attributes\DataProvider;
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

        APIResponse::shouldReceive('badRequest');

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

    #[Test]
    public function findIncludingReturnsValidationErrorsWithInvalidQueryStringParams(): void
    {
        $request = Request::create('/?include[0][name]=posts', 'GET');
        $this->app->instance('request', $request);

        $service = \Mockery::mock(ListService::class);
        $service->shouldNotReceive('setSearchConfiguration');

        APIResponse::shouldReceive('badRequest')
            ->once()
        ;

        $this->resourceControllerClass->findIncluding(1, $service);
    }

    #[Test]
    public function findIncludingReturnsOkWhenElementExists(): void
    {
        $request = Request::create('/?include[0]=posts', 'GET');
        $this->app->instance('request', $request);

        $identifier = 123;
        $expectedElement = ['id' => 123, 'title' => 'Post'];

        $service = \Mockery::mock(ListService::class);
        $service->shouldReceive('setSearchConfiguration')
            ->andReturnSelf()
        ;

        $service->shouldReceive('findIncluding')
            ->andReturn($expectedElement)
        ;

        APIResponse::shouldReceive('ok')
            ->once()
            ->with($expectedElement)
        ;

        APIResponse::shouldNotReceive('notFound');
        APIResponse::shouldNotReceive('badRequest');

        $this->resourceControllerClass->findIncluding($identifier, $service);
    }

    #[Test]
    public function findIncludingReturnsNotFoundWhenElementDoesNotExist(): void
    {
        $request = Request::create('/?include[0]=posts', 'GET');
        $this->app->instance('request', $request);

        $identifier = 123;

        $expectedConfig = [
            'perPage' => null,
            'sortField' => null,
            'sortDirection' => null,
            'keyword' => null,
            'filters' => null,
            'belongsTo' => null,
            'relationId' => null,
            'include' => ['posts'],
        ];

        $service = \Mockery::mock(ListService::class);
        $service->shouldReceive('setSearchConfiguration')
            ->once()
            ->with($expectedConfig)
            ->andReturnSelf()
        ;

        $service->shouldReceive('findIncluding')
            ->once()
            ->with($identifier)
            ->andReturn(null)
        ;

        APIResponse::shouldReceive('notFound')
            ->once()
        ;

        APIResponse::shouldNotReceive('ok');

        $this->resourceControllerClass->findIncluding($identifier, $service);
    }

    #[Test]
    #[DataProvider('malformedQueryStrings')]
    public function itRespondsBadRequestWithMalformedParams(string $queryString): void
    {
        $this->app->instance('request', Request::create('/posts?'.$queryString, 'GET'));
        Post::factory()->create();

        try {
            $this->resourceControllerClass->list((new ListService())->setListModel(Post::class));
            $this->fail('Expected a bad request response');
        } catch (HttpResponseException $e) {
            $this->assertSame(400, $e->getResponse()->getStatusCode());
        }
    }

    public static function malformedQueryStrings(): array
    {
        return [
            'invalid direction' => ['sortField=title&sortDirection=foo'],
            'direction as array' => ['sortField=title&sortDirection[]=asc'],
            'filter is not an array' => ['filters[]=abc'],
            'filter without name' => ['filters[0][active]=true&filters[0][value]=x'],
            'filter name as array' => ['filters[0][name][]=title&filters[0][active]=true'],
        ];
    }

    #[Test]
    #[DataProvider('validQueryStrings')]
    public function itKeepsAcceptingValidParams(string $queryString, int $expectedItems): void
    {
        $this->app->instance('request', Request::create('/posts?'.$queryString, 'GET'));
        Post::factory()->create(['title' => 'Post']);

        $response = $this->resourceControllerClass->list((new ListService())->setListModel(Post::class));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedItems, json_decode($response->getContent(), true)['data']['countItems']);
    }

    public static function validQueryStrings(): array
    {
        return [
            'lowercase direction' => ['sortField=title&sortDirection=desc', 1],
            'uppercase direction' => ['sortField=title&sortDirection=DESC', 1],
            'inactive filter without value' => ['filters[0][name]=title&filters[0][active]=false', 1],
            'filter without active is not processed' => ['filters[0][name]=title&filters[0][value]=Other', 1],
            'active filter' => ['filters[0][name]=title&filters[0][active]=true&filters[0][value]=Other', 0],
            'active filter without value' => ['filters[0][name]=title&filters[0][active]=true', 0],
            'unknown include' => ['include=nope', 1],
            'dangerous include' => ['include=truncate', 1],
        ];
    }

    #[Test]
    public function itSerializesPaginatedListWithResourceKeepingPaginationKeys(): void
    {
        $this->app->instance('request', Request::create('/posts?per_page=2', 'GET'));
        Post::factory()->create(['title' => 'Post 1', 'created_at' => '2026-01-15 10:00:00']);
        Post::factory()->count(2)->create();

        $service = (new ListService())->setListModel(Post::class)->setListResource(LocalizedPostResource::class);

        $json = json_decode($this->resourceControllerClass->list($service)->getContent(), true);

        $this->assertSame('3 items found', $json['message']);
        $this->assertSame(3, $json['data']['countItems']);
        $this->assertSame(1, $json['data']['result']['current_page']);
        $this->assertEquals(2, $json['data']['result']['per_page']);
        $this->assertNotNull($json['data']['result']['next_page_url']);
        $this->assertCount(2, $json['data']['result']['data']);
        $this->assertSame([
            'title' => 'Post 1',
            'created_at_es' => '15 de enero de 2026',
            'created_at_en' => 'January 15, 2026',
        ], $json['data']['result']['data'][0]);
    }

    #[Test]
    public function itSerializesNotPaginatedListWithResource(): void
    {
        $this->app->instance('request', Request::create('/posts', 'GET'));
        Post::factory()->count(2)->create();

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setListResource(LocalizedPostResource::class)
            ->setPaginated(false)
        ;

        $json = json_decode($this->resourceControllerClass->list($service)->getContent(), true);

        $this->assertSame('2 items found', $json['message']);
        $this->assertCount(2, $json['data']);
        $this->assertArrayNotHasKey('id', $json['data'][0]);
        $this->assertArrayHasKey('title', $json['data'][0]);
    }

    #[Test]
    public function itSerializesFoundElementWithResource(): void
    {
        $this->app->instance('request', Request::create('/posts/1', 'GET'));
        $post = Post::factory()->create(['title' => 'Post 1']);

        $service = (new ListService())->setListModel(Post::class)->setListResource(LocalizedPostResource::class);

        $json = json_decode($this->resourceControllerClass->findIncluding($post->id, $service)->getContent(), true);

        $this->assertSame('Post 1', $json['data']['title']);
        $this->assertArrayNotHasKey('id', $json['data']);
    }
}
