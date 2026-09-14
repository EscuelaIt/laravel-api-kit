<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use EscuelaIT\APIKit\Exceptions\InvalidResourceClassException;
use EscuelaIT\APIKit\ListService;
use EscuelaIT\Test\Fixtures\Post;
use EscuelaIT\Test\Fixtures\PostResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 *
 * @coversNothing
 */
class ListServiceResourceTest extends TestCase
{
    #[Test]
    public function itWrapsUnpaginatedResultsIntoAResourceCollection(): void
    {
        Post::factory()->count(3)->create();

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setPaginated(false)
            ->setSearchConfiguration([
                'sortField' => 'id',
                'sortDirection' => 'asc',
            ])
        ;

        $items = $service->getResults();
        $wrapped = $service->wrapCollection($items, PostResource::class);

        $this->assertInstanceOf(AnonymousResourceCollection::class, $wrapped);
        $this->assertCount(3, $wrapped);
        $this->assertContainsOnlyInstancesOf(PostResource::class, $wrapped);
        $this->assertSame($items[0]->id, $wrapped[0]->id);
    }

    #[Test]
    public function itWrapsPaginatedResultsPreservingPaginationMetadata(): void
    {
        Post::factory()->count(15)->create();

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setSearchConfiguration([
                'perPage' => 10,
                'sortField' => 'id',
                'sortDirection' => 'asc',
            ])
        ;

        $results = $service->getResults();
        $wrapped = $service->wrapPaginated($results, PostResource::class);

        $this->assertEquals(15, $wrapped['countItems']);
        $this->assertCount(10, $wrapped['result']);
        $this->assertContainsOnlyInstancesOf(PostResource::class, $wrapped['result']->items());
        $this->assertEquals(10, $wrapped['result']->perPage());
    }

    #[Test]
    public function itWrapsAFoundModelInFindIncluding(): void
    {
        $post = Post::factory()->create(['title' => 'Post 1']);

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setSearchConfiguration([])
        ;

        $model = $service->findIncluding($post->id);
        $wrapped = $service->wrapModel($model, PostResource::class);

        $this->assertInstanceOf(PostResource::class, $wrapped);
        $this->assertEquals($post->id, $wrapped->id);
    }

    #[Test]
    public function itDoesNotWrapNullWhenModelIsNotFound(): void
    {
        Post::factory()->count(2)->create();

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setSearchConfiguration([])
        ;

        $model = $service->findIncluding(999);
        $wrapped = $service->wrapModel($model, PostResource::class);

        $this->assertNull($wrapped);
    }

    #[Test]
    public function itThrowsExceptionWhenWrappingCollectionWithAnInvalidResourceClass(): void
    {
        Post::factory()->count(2)->create();

        $service = (new ListService())->setListModel(Post::class);
        $items = $service->setPaginated(false)->getResults();

        $this->expectException(InvalidResourceClassException::class);

        $service->wrapCollection($items, Post::class);
    }

    #[Test]
    public function itThrowsExceptionWhenWrappingPaginatedResultsWithAnInvalidResourceClass(): void
    {
        Post::factory()->count(2)->create();

        $service = (new ListService())->setListModel(Post::class);
        $results = $service->getResults();

        $this->expectException(InvalidResourceClassException::class);

        $service->wrapPaginated($results, Post::class);
    }

    #[Test]
    public function itThrowsExceptionWhenWrappingModelWithAnInvalidResourceClass(): void
    {
        $post = Post::factory()->create();

        $service = (new ListService())->setListModel(Post::class);

        $this->expectException(InvalidResourceClassException::class);

        $service->wrapModel($post, Post::class);
    }
}
