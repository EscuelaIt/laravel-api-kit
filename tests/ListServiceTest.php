<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use EscuelaIT\APIKit\Exceptions\ListModelNotDefinedException;
use EscuelaIT\APIKit\ListService;
use EscuelaIT\Test\Filters\TitleContainsFilter;
use EscuelaIT\Test\Fixtures\Comment;
use EscuelaIT\Test\Fixtures\Post;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 *
 * @coversNothing
 */
class ListServiceTest extends TestCase
{
    #[Test]
    public function itListsNoPostsWhenDbIsEmpty(): void
    {
        // Arrange: DB vacía
        $service = (new ListService())
            ->setListModel(Post::class)
            ->setSearchConfiguration([
                'perPage' => 10,
                'sortField' => 'id',
                'sortDirection' => 'asc',
            ])
        ;

        // Act
        $results = $service->getResults();

        // Assert
        $this->assertEquals(0, $results['countItems']);
        $this->assertCount(0, $results['result']);
    }

    #[Test]
    public function itThrowsExceptionWhenListModelNotDefined(): void
    {
        $this->expectException(ListModelNotDefinedException::class);

        $service = (new ListService())
            ->setSearchConfiguration([
                'perPage' => 10,
                'sortField' => 'id',
                'sortDirection' => 'asc',
            ])
        ;

        $service->getResults();
    }

    #[Test]
    public function itListsPostsWithPagination(): void
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

        $this->assertEquals(15, $results['countItems']);
        $this->assertCount(10, $results['result']); // 10 por página
    }

    #[Test]
    public function itListsPostsWithoutPagination(): void
    {
        Post::factory()->count(8)->create();

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setSearchConfiguration([
                'perPage' => 10,
                'sortField' => 'id',
                'sortDirection' => 'asc',
            ])
            ->setPaginated(false)
        ;

        $results = $service->getResults();

        $this->assertCount(8, $results);
    }

    #[Test]
    public function itListsPostsWithSorting(): void
    {
        Post::factory()->create(['title' => 'B Post']);
        Post::factory()->create(['title' => 'A Post']);
        Post::factory()->create(['title' => 'C Post']);

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setSearchConfiguration([
                'sortField' => 'title',
                'sortDirection' => 'asc',
            ])
        ;

        $results = $service->getResults();

        $this->assertEquals('A Post', $results['result'][0]->title);
        $this->assertEquals('B Post', $results['result'][1]->title);
        $this->assertEquals('C Post', $results['result'][2]->title);
    }

    #[Test]
    public function itListsPostsWithoutFilters(): void
    {
        Post::factory()->count(5)->create();

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setSearchConfiguration([
                'perPage' => 10,
                'sortField' => 'id',
                'sortDirection' => 'asc',
            ])
        ;

        $results = $service->getResults();

        $this->assertEquals(5, $results['countItems']);
        $this->assertCount(5, $results['result']);
    }

    #[Test]
    public function itListsPostsWithKeywordSearch(): void
    {
        Post::factory()->create(['title' => 'Learn Laravel', 'status' => 'published']);
        Post::factory()->create(['title' => 'JavaScript Basics', 'status' => 'published']);
        Post::factory()->create(['title' => 'Why learn Object Oriented PHP', 'status' => 'draft']);
        Post::factory()->create(['title' => 'JavaScript Basics', 'status' => 'published']);

        $service = (new class extends ListService {
            protected function applyKeywordFilter(?string $keyword): void
            {
                $this->query->similar($keyword);
            }
        })
            ->setListModel(Post::class)
            ->setSearchConfiguration([
                'keyword' => 'Learn',
                'perPage' => 10,
                'sortField' => 'id',
                'sortDirection' => 'asc',
            ])
        ;

        $results = $service->getResults();

        $this->assertEquals(2, $results['countItems']);
        $this->assertTrue(
            $results['result']->every(fn ($post) => str_contains(strtolower($post->title), 'learn'))
        );
    }

    #[Test]
    public function itFiltersAndPaginatesPosts(): void
    {
        Post::factory()->count(3)->create(['status' => 'published']);
        Post::factory()->count(2)->create(['status' => 'draft']);

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setSearchConfiguration([
                'filters' => [
                    [
                        'name' => 'status',
                        'value' => 'published',
                        'active' => true,
                    ],
                ],
                'perPage' => 2,
                'sortField' => 'id',
                'sortDirection' => 'asc',
            ])
        ;

        $results = $service->getResults();

        $this->assertEquals(3, $results['countItems']);
        $this->assertCount(2, $results['result']);      // 2 por página
        $this->assertTrue(
            $results['result']->every(fn ($post) => 'published' === $post->status)
        );
    }

    #[Test]
    public function itAppliesCustomTitleContainsFilter(): void
    {
        Post::factory()->count(2)->create(['title' => 'Learn PHP', 'status' => 'published']);
        Post::factory()->count(1)->create(['title' => 'Laravel Tips', 'status' => 'published']);
        Post::factory()->count(2)->create(['title' => 'Advanced JavaScript', 'status' => 'draft']);

        $service = (new class extends ListService {
            protected function customFilters(): array
            {
                return [new TitleContainsFilter()];
            }
        })
            ->setListModel(Post::class)
            ->setSearchConfiguration([
                'filters' => [
                    [
                        'name' => 'title_contains',
                        'value' => 'PHP',
                        'active' => true,
                    ],
                ],
                'perPage' => 10,
            ])
        ;

        $results = $service->getResults();

        $this->assertEquals(2, $results['countItems']);
        $this->assertTrue(
            $results['result']->every(fn ($post) => str_contains($post->title, 'PHP'))
        );
    }

    #[Test]
    public function itIncludesAllowedRelationsWhenRequested(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->count(2)->create(['post_id' => $post->id]);

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableIncludes(['comments'])
            ->setSearchConfiguration([
                'include' => 'comments',
                'perPage' => 10,
                'sortField' => 'id',
            ])
        ;

        $results = $service->getResults();

        $this->assertTrue(
            $results['result']->every(fn ($item) => $item->relationLoaded('comments'))
        );
        $this->assertEquals(2, $results['result'][0]->comments->count());
    }

    #[Test]
    public function itDoesNotIncludeRelationsNotInAvailableIncludes(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->count(2)->create(['post_id' => $post->id]);

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableIncludes(['other'])
            ->setSearchConfiguration([
                'include' => 'comments',
                'perPage' => 10,
                'sortField' => 'id',
            ])
        ;

        $results = $service->getResults();

        $this->assertTrue(
            $results['result']->every(fn ($item) => !$item->relationLoaded('comments'))
        );
    }

    #[Test]
    public function itAppliesScopeViaBelongsToAndRelationId(): void
    {
        // Arrange: crear posts con IDs específicos
        Post::factory()->create(['id' => 1, 'title' => 'Post 1']);
        Post::factory()->create(['id' => 2, 'title' => 'Post 2']);
        Post::factory()->create(['id' => 3, 'title' => 'Post 3']);
        Post::factory()->create(['id' => 4, 'title' => 'Post 4']);
        Post::factory()->create(['id' => 5, 'title' => 'Post 5']);

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setSearchConfiguration([
                'belongsTo' => 'greaterThanId',
                'relationId' => 2,
                'perPage' => 10,
                'sortField' => 'id',
                'sortDirection' => 'asc',
            ])
        ;

        // Act
        $results = $service->getResults();

        // Assert
        $this->assertEquals(3, $results['countItems']); // Posts 3, 4, 5
        $this->assertCount(3, $results['result']);
        $this->assertTrue(
            $results['result']->every(fn ($post) => $post->id > 2)
        );
    }

    #[Test]
    public function itAppliesScopeWhenItIsInAvailableScopes(): void
    {
        // Arrange: crear posts con IDs específicos
        Post::factory()->create(['id' => 1, 'title' => 'Post 1']);
        Post::factory()->create(['id' => 2, 'title' => 'Post 2']);
        Post::factory()->create(['id' => 3, 'title' => 'Post 3']);
        Post::factory()->create(['id' => 4, 'title' => 'Post 4']);
        Post::factory()->create(['id' => 5, 'title' => 'Post 5']);

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableScopes(['greaterThanId'])
            ->setSearchConfiguration([
                'belongsTo' => 'greaterThanId',
                'relationId' => 2,
                'perPage' => 10,
                'sortField' => 'id',
                'sortDirection' => 'asc',
            ])
        ;

        // Act
        $results = $service->getResults();

        // Assert
        $this->assertEquals(3, $results['countItems']); // Posts 3, 4, 5
        $this->assertCount(3, $results['result']);
        $this->assertTrue(
            $results['result']->every(fn ($post) => $post->id > 2)
        );
    }

    #[Test]
    public function itDoesNotApplyScopeWhenItIsNotInAvailableScopes(): void
    {
        // Arrange: crear posts con IDs específicos
        Post::factory()->create(['id' => 1, 'title' => 'Post 1']);
        Post::factory()->create(['id' => 2, 'title' => 'Post 2']);
        Post::factory()->create(['id' => 3, 'title' => 'Post 3']);
        Post::factory()->create(['id' => 4, 'title' => 'Post 4']);
        Post::factory()->create(['id' => 5, 'title' => 'Post 5']);

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableScopes(['otherScope'])
            ->setSearchConfiguration([
                'belongsTo' => 'greaterThanId',
                'relationId' => 2,
                'perPage' => 10,
                'sortField' => 'id',
                'sortDirection' => 'asc',
            ])
        ;

        // Act
        $results = $service->getResults();

        // Assert
        $this->assertEquals(5, $results['countItems']); // Todos los posts
        $this->assertCount(5, $results['result']);
    }

    #[Test]
    public function itLimitsPerPageToMaxPerPageWhenRequestedSizeExceedsLimit(): void
    {
        // Arrange: crear 20 posts
        Post::factory()->count(20)->create();

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setMaxPerPage(5)
            ->setSearchConfiguration([
                'perPage' => 100, // Solicitar 100 elementos
                'sortField' => 'id',
                'sortDirection' => 'asc',
            ])
        ;

        // Act
        $results = $service->getResults();

        // Assert
        $this->assertEquals(20, $results['countItems']); // Total de posts
        $this->assertCount(5, $results['result']); // Acotado a maxPerPage (5)
    }

    #[Test]
    public function itDoesNotLimitPerPageWhenRequestedSizeIsBelowMaxPerPage(): void
    {
        // Arrange: crear 20 posts
        Post::factory()->count(20)->create();

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setMaxPerPage(10)
            ->setSearchConfiguration([
                'perPage' => 5, // Solicitar 5 elementos (menor que maxPerPage)
                'sortField' => 'id',
                'sortDirection' => 'asc',
            ])
        ;

        // Act
        $results = $service->getResults();

        // Assert
        $this->assertEquals(20, $results['countItems']); // Total de posts
        $this->assertCount(5, $results['result']); // Respeta el perPage solicitado
    }

    #[Test]
    public function itLimitsFiltersToMaxFiltersWhenNumberExceedsLimit(): void
    {
        // Arrange: crear posts con diferentes estados y países
        Post::factory()->create(['status' => 'published', 'title' => 'Post A']);
        Post::factory()->create(['status' => 'draft', 'title' => 'Post B']);
        Post::factory()->create(['status' => 'archived', 'title' => 'Post C']);
        Post::factory()->create(['status' => 'published', 'title' => 'Post D']);

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setMaxFilters(1)
            ->setSearchConfiguration([
                'filters' => [
                    [
                        'name' => 'status',
                        'value' => 'published',
                        'active' => true,
                    ],
                    [
                        'name' => 'title',
                        'value' => 'Post A',
                        'active' => true,
                    ],
                ],
                'perPage' => 10,
            ])
        ;

        // Act
        $results = $service->getResults();

        // Assert: Solo se aplica el primer filtro (status = published)
        $this->assertEquals(2, $results['countItems']);
    }

    #[Test]
    public function itDoesNotLimitFiltersWhenNumberIsBelowMaxFilters(): void
    {
        // Arrange: crear posts
        Post::factory()->count(2)->create(['status' => 'published']);
        Post::factory()->count(3)->create(['status' => 'draft']);

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setMaxFilters(5)
            ->setSearchConfiguration([
                'filters' => [
                    [
                        'name' => 'status',
                        'value' => 'published',
                        'active' => true,
                    ],
                ],
                'perPage' => 10,
            ])
        ;

        // Act
        $results = $service->getResults();

        // Assert: Se aplica el filtro normalmente
        $this->assertEquals(2, $results['countItems']);
        $this->assertTrue(
            $results['result']->every(fn ($post) => 'published' === $post->status)
        );
    }
}
