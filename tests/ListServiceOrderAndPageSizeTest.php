<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use EscuelaIT\APIKit\ListService;
use EscuelaIT\Test\Fixtures\Comment;
use EscuelaIT\Test\Fixtures\Post;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 *
 * @coversNothing
 */
class ListServiceOrderAndPageSizeTest extends TestCase
{
    #[Test]
    public function itOrdersByIdentifierWhenNoSortFieldIsRequested(): void
    {
        Post::factory()->create(['title' => 'B']);
        Post::factory()->create(['title' => 'A']);

        $service = new class extends ListService {
            protected string $listModel = Post::class;
            protected string $identifierField = 'title';
        };

        $this->assertSame('A', $service->getResults()['result'][0]->title);
    }

    #[Test]
    public function itUsesIdentifierAsLastSortCriterion(): void
    {
        Post::factory()->count(3)->create(['status' => 'draft']);

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableSortFields(['status'])
            ->setSearchConfiguration(['sortField' => 'status', 'sortDirection' => 'desc'])
        ;

        $this->assertStringEndsWith('order by "status" desc, "posts"."id" asc limit 11 offset 0', $this->listQuery($service));
    }

    #[Test]
    public function itDoesNotAddIdentifierWhenItIsTheSortField(): void
    {
        $first = Post::factory()->create();
        $second = Post::factory()->create();

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableSortFields(['id'])
            ->setSearchConfiguration(['sortField' => 'id', 'sortDirection' => 'desc'])
        ;

        $this->assertStringEndsWith('order by "id" desc limit 11 offset 0', $this->listQuery($service));
        $this->assertSame([$second->id, $first->id], collect($service->getResults()['result']->items())->pluck('id')->all());
    }

    #[Test]
    public function itOrdersByIdentifierWhenSortFieldIsNotAllowed(): void
    {
        Post::factory()->create();

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setSearchConfiguration(['sortField' => 'id', 'sortDirection' => 'desc'])
        ;

        $this->assertStringEndsWith('order by "posts"."id" asc limit 11 offset 0', $this->listQuery($service));
    }

    #[Test]
    public function itKeepsOrderDefinedInCreateQueryBeforeIdentifier(): void
    {
        Post::factory()->create();

        $service = new class extends ListService {
            protected string $listModel = Post::class;

            protected function createQuery()
            {
                return parent::createQuery()->orderBy('title');
            }
        };

        $this->assertStringEndsWith('order by "title" asc, "posts"."id" asc limit 11 offset 0', $this->listQuery($service));
    }

    #[Test]
    public function itDoesNotUseOrderInCountQuery(): void
    {
        Post::factory()->count(2)->create();

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableSortFields(['title'])
            ->setSearchConfiguration(['sortField' => 'title'])
        ;

        DB::enableQueryLog();
        $results = $service->getResults();

        $this->assertEquals(2, $results['countItems']);
        $countQuery = DB::getQueryLog()[0]['query'];
        $this->assertStringContainsString('count(*)', $countQuery);
        $this->assertStringNotContainsString('order by', $countQuery);
    }

    #[Test]
    public function itQualifiesIdentifierInQueriesWithJoins(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->create(['post_id' => $post->id]);

        $service = new class extends ListService {
            protected string $listModel = Post::class;

            protected function createQuery()
            {
                return parent::createQuery()->select('posts.*')->join('comments', 'comments.post_id', '=', 'posts.id');
            }
        };

        $results = $service->getResults();

        $this->assertEquals(1, $results['countItems']);
        $this->assertSame($post->id, $results['result'][0]->id);
    }

    #[Test]
    #[DataProvider('queriesThatCannotBeSortedByIdentifier')]
    public function itDoesNotOrderByIdentifierWhenQueryCannotBeSortedByIt(callable $constrain): void
    {
        Post::factory()->create();

        $service = new class($constrain) extends ListService {
            protected string $listModel = Post::class;

            public function __construct(private $constrain) {}

            protected function createQuery()
            {
                return ($this->constrain)(parent::createQuery());
            }
        };

        $this->assertStringNotContainsString('order by', $this->listQuery($service));
    }

    public static function queriesThatCannotBeSortedByIdentifier(): array
    {
        return [
            'distinct' => [fn ($query) => $query->select('status')->distinct()],
            'group by' => [fn ($query) => $query->select('status')->groupBy('status')],
            'union' => [fn ($query) => $query->select('status')->union(Post::query()->select('status'))],
        ];
    }

    #[Test]
    public function itReturnsIdsInStableOrderWhenLimited(): void
    {
        Post::factory()->create(['title' => 'B']);
        Post::factory()->create(['title' => 'A']);

        $service = new class extends ListService {
            protected string $listModel = Post::class;
            protected string $identifierField = 'title';
        };

        $this->assertSame(['A'], $service->setMaxIds(1)->getAllIds()->all());
    }

    #[Test]
    #[DataProvider('lastPages')]
    public function itReturnsLastPage(int $items, int $perPage, int $expected): void
    {
        Post::factory()->count($items)->create();

        $results = (new ListService())
            ->setListModel(Post::class)
            ->setSearchConfiguration(['perPage' => $perPage])
            ->getResults()
        ;

        $this->assertSame($expected, $results['lastPage']);
    }

    public static function lastPages(): array
    {
        return [
            'no items' => [0, 10, 1],
            'fewer items than a page' => [3, 10, 1],
            'exact pages' => [4, 2, 2],
            'incomplete last page' => [5, 2, 3],
        ];
    }

    #[Test]
    public function itCalculatesLastPageWithCappedPageSize(): void
    {
        Post::factory()->count(5)->create();

        $results = (new ListService())
            ->setListModel(Post::class)
            ->setMaxPerPage(2)
            ->setSearchConfiguration(['perPage' => 100])
            ->getResults()
        ;

        $this->assertSame(3, $results['lastPage']);
    }

    #[Test]
    #[DataProvider('pageSizes')]
    public function itNormalizesPageSize(mixed $requested, ?int $maxPerPage, int $expected): void
    {
        Post::factory()->count(20)->create();

        $results = (new ListService())
            ->setListModel(Post::class)
            ->setMaxPerPage($maxPerPage)
            ->setSearchConfiguration(['perPage' => $requested])
            ->getResults()
        ;

        $this->assertSame($expected, $results['result']->perPage());
        $this->assertCount($expected, $results['result']->items());
    }

    public static function pageSizes(): array
    {
        return [
            'integer' => [5, null, 5],
            'numeric string' => ['2', null, 2],
            'zero' => [0, null, 1],
            'negative' => [-3, null, 1],
            'zero with maximum' => [0, 5, 1],
            'below maximum' => [3, 5, 3],
            'above maximum' => [8, 5, 5],
            'numeric string above maximum' => ['8', 5, 5],
            'maximum of zero' => [8, 0, 1],
        ];
    }

    private function listQuery(ListService $service): string
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $service->getResults();
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        return end($log)['query'];
    }
}
