<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use EscuelaIT\APIKit\Exceptions\InvalidResourceClassException;
use EscuelaIT\APIKit\Exceptions\ListModelNotDefinedException;
use EscuelaIT\APIKit\Exceptions\MissingRequiredScopeException;
use EscuelaIT\APIKit\ListService;
use EscuelaIT\Test\Filters\TitleContainsFilter;
use EscuelaIT\Test\Fixtures\Comment;
use EscuelaIT\Test\Fixtures\LocalizedPostResource;
use EscuelaIT\Test\Fixtures\Post;
use Illuminate\Http\Resources\Json\ResourceCollection;
use PHPUnit\Framework\Attributes\DataProvider;
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
            ->setAvailableSortFields(['title'])
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
            ->setAvailableFilterColumns(['status'])
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
            ->setAvailableFilterColumns(['status', 'title'])
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
            ->setAvailableFilterColumns(['status'])
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

    #[Test]
    public function itFindsSinglePostByIdentifier(): void
    {
        // Arrange: crear posts
        $post1 = Post::factory()->create(['title' => 'Post 1', 'status' => 'published']);
        $post2 = Post::factory()->create(['title' => 'Post 2', 'status' => 'draft']);
        $post3 = Post::factory()->create(['title' => 'Post 3', 'status' => 'published']);

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setSearchConfiguration([])
        ;

        // Act
        $result = $service->findIncluding($post2->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($post2->id, $result->id);
        $this->assertEquals('Post 2', $result->title);
        $this->assertEquals('draft', $result->status);
    }

    #[Test]
    public function itReturnsNullWhenPostNotFound(): void
    {
        // Arrange: crear posts
        Post::factory()->count(3)->create();

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setSearchConfiguration([])
        ;

        // Act
        $result = $service->findIncluding(999); // ID que no existe

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function itFindsPostWithIncludedRelations(): void
    {
        // Arrange: crear post con comentarios
        $post = Post::factory()->create(['title' => 'Post with Comments']);
        Comment::factory()->count(3)->create(['post_id' => $post->id]);

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableIncludes(['comments'])
            ->setSearchConfiguration([
                'include' => 'comments',
            ])
        ;

        // Act
        $result = $service->findIncluding($post->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($post->id, $result->id);
        $this->assertTrue($result->relationLoaded('comments'));
        $this->assertEquals(3, $result->comments->count());
    }

    #[Test]
    public function itFindsPostWithoutIncludingNotAvailableRelations(): void
    {
        // Arrange: crear post con comentarios
        $post = Post::factory()->create(['title' => 'Post with Comments']);
        Comment::factory()->count(2)->create(['post_id' => $post->id]);

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableIncludes(['other_relation'])  // No incluye 'comments'
            ->setSearchConfiguration([
                'include' => 'comments',
            ])
        ;

        // Act
        $result = $service->findIncluding($post->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($post->id, $result->id);
        $this->assertFalse($result->relationLoaded('comments'));
    }

    #[Test]
    public function itFindsPostWithStringIncludeParam(): void
    {
        $post = Post::factory()->create(['title' => 'Post with Comments']);
        Comment::factory()->count(1)->create(['post_id' => $post->id]);

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableIncludes(['comments'])
            ->setSearchConfiguration([
                'include' => 'comments',
            ])
        ;

        // Act
        $result = $service->findIncluding($post->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertTrue($result->relationLoaded('comments'));
        $this->assertEquals(1, $result->comments->count());
    }

    #[Test]
    public function itReturnsAllIds(): void
    {
        // Arrange: crear posts
        $post1 = Post::factory()->create(['title' => 'Post 1', 'status' => 'published']);
        $post2 = Post::factory()->create(['title' => 'Post 2', 'status' => 'draft']);
        $post3 = Post::factory()->create(['title' => 'Post 3', 'status' => 'published']);

        $service = (new ListService())
            ->setListModel(Post::class)
        ;

        // Act
        $ids = $service->getAllIds();

        // Assert
        $this->assertCount(3, $ids);
        $this->assertContains($post1->id, $ids);
        $this->assertContains($post2->id, $ids);
        $this->assertContains($post3->id, $ids);
    }

    #[Test]
    public function itLimitsIdsToMaxIds(): void
    {
        // Arrange: crear posts
        $post1 = Post::factory()->create(['title' => 'Post 1', 'status' => 'published']);
        $post2 = Post::factory()->create(['title' => 'Post 2', 'status' => 'draft']);
        $post3 = Post::factory()->create(['title' => 'Post 3', 'status' => 'published']);

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setMaxIds(1)
        ;

        // Act
        $ids = $service->getAllIds();

        // Assert
        $this->assertCount(1, $ids);
        // El id devuelto debe ser uno de los creados
        $this->assertContains($ids[0], [$post1->id, $post2->id, $post3->id]);
    }

    #[Test]
    public function itReturnsTheSubclassFromAllSetters(): void
    {
        $service = new class extends ListService {
            public function subclassMethod(): string
            {
                return 'subclass';
            }
        };

        $this->assertSame('subclass', $service
            ->setListModel(Post::class)
            ->setIdentifierField('id')
            ->setListResource(null)
            ->setPaginated(true)
            ->setAvailableFilterColumns([])
            ->setAvailableScopes([])
            ->setAvailableIncludes([])
            ->setAvailableSortFields([])
            ->setMaxPerPage(null)
            ->setMaxFilters(null)
            ->setMaxIds(null)
            ->setRequiredScope(null)
            ->setSearchConfiguration([])
            ->setSearchConfigurationValue('keyword', null)
            ->subclassMethod());
    }

    #[Test]
    public function itUsesIdentifierFieldConfiguredWithSetter(): void
    {
        Post::factory()->create(['title' => 'First']);
        $post = Post::factory()->create(['title' => 'Second']);

        $service = (new ListService())->setListModel(Post::class)->setIdentifierField('title');

        $this->assertTrue($post->is($service->findIncluding('Second')));
        $this->assertSame(['First', 'Second'], $service->getAllIds()->all());
    }

    #[Test]
    public function itIgnoresClientOptionsByDefault(): void
    {
        $post = Post::factory()->create(['title' => 'A', 'status' => 'draft']);
        Post::factory()->create(['title' => 'B', 'status' => 'published']);
        Comment::factory()->create(['post_id' => $post->id]);

        $results = (new ListService())
            ->setListModel(Post::class)
            ->setSearchConfiguration([
                'filters' => [['name' => 'status', 'value' => 'published', 'active' => true]],
                'sortField' => 'title',
                'sortDirection' => 'desc',
                'belongsTo' => 'greaterThanId',
                'relationId' => 100,
                'include' => 'comments',
            ])
            ->getResults()
        ;

        $this->assertEquals(2, $results['countItems']);
        $this->assertSame('A', $results['result'][0]->title);
        $this->assertSame([], $results['result'][0]->getRelations());
    }

    #[Test]
    public function itIgnoresDangerousIncludeByDefault(): void
    {
        Post::factory()->create();

        (new ListService())
            ->setListModel(Post::class)
            ->setSearchConfiguration(['include' => 'truncate'])
            ->getResults()
        ;

        $this->assertSame(1, Post::query()->count());
    }

    #[Test]
    public function itAllowsAnyFilterAndScopeWhenAvailableListsAreNull(): void
    {
        Post::factory()->create(['id' => 1, 'status' => 'published']);
        Post::factory()->create(['id' => 2, 'status' => 'published']);
        Post::factory()->create(['id' => 3, 'status' => 'draft']);

        $results = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableFilterColumns(null)
            ->setAvailableScopes(null)
            ->setSearchConfiguration([
                'filters' => [['name' => 'status', 'value' => 'published', 'active' => true]],
                'belongsTo' => 'greaterThanId',
                'relationId' => 1,
            ])
            ->getResults()
        ;

        $this->assertEquals(1, $results['countItems']);
        $this->assertSame(2, $results['result'][0]->id);
    }

    #[Test]
    public function itIgnoresFilterColumnNotIncludedInAvailableFilterColumns(): void
    {
        Post::factory()->create(['title' => 'A', 'status' => 'draft']);

        $results = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableFilterColumns(['title'])
            ->setSearchConfiguration([
                'filters' => [['name' => 'status', 'value' => 'published', 'active' => true]],
            ])
            ->getResults()
        ;

        $this->assertEquals(1, $results['countItems']);
    }

    #[Test]
    public function itAppliesCustomFiltersWithDefaultAvailableLists(): void
    {
        Post::factory()->create(['title' => 'Laravel']);
        Post::factory()->create(['title' => 'Other']);

        $service = new class extends ListService {
            protected string $listModel = Post::class;

            protected function customFilters(): array
            {
                return [new TitleContainsFilter()];
            }
        };

        $results = $service
            ->setSearchConfiguration(['filters' => [['name' => 'title_contains', 'value' => 'Lara', 'active' => true]]])
            ->getResults()
        ;

        $this->assertEquals(1, $results['countItems']);
    }

    #[Test]
    public function itIgnoresMalformedFilters(): void
    {
        Post::factory()->count(2)->create();

        $results = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableFilterColumns(null)
            ->setSearchConfiguration(['filters' => [
                'abc',
                ['active' => true, 'value' => 'x'],
                ['name' => ['title'], 'active' => true, 'value' => 'x'],
            ]])
            ->getResults()
        ;

        $this->assertEquals(2, $results['countItems']);
    }

    #[Test]
    public function itTreatsFilterWithoutActiveAsInactive(): void
    {
        Post::factory()->count(2)->create(['title' => 'Post']);

        $results = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableFilterColumns(null)
            ->setSearchConfiguration(['filters' => [['name' => 'title', 'value' => 'Other']]])
            ->getResults()
        ;

        $this->assertEquals(2, $results['countItems']);
    }

    #[Test]
    public function itDoesNotFailWithActiveFilterWithoutValue(): void
    {
        Post::factory()->count(2)->create();

        $results = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableFilterColumns(['title'])
            ->setSearchConfiguration(['filters' => [
                ['name' => 'title', 'active' => true],
            ]])
            ->getResults()
        ;

        $this->assertEquals(0, $results['countItems']);
    }

    #[Test]
    public function itDoesNotFailWithCustomFilterWithoutValue(): void
    {
        Post::factory()->count(2)->create();

        $service = new class extends ListService {
            protected string $listModel = Post::class;

            protected function customFilters(): array
            {
                return [new TitleContainsFilter()];
            }
        };

        $results = $service
            ->setSearchConfiguration(['filters' => [['name' => 'title_contains', 'active' => true]]])
            ->getResults()
        ;

        $this->assertEquals(2, $results['countItems']);
    }

    #[Test]
    public function itSortsByAnyFieldWhenAvailableSortFieldsIsNull(): void
    {
        Post::factory()->create(['title' => 'A']);
        Post::factory()->create(['title' => 'B']);

        $results = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableSortFields(null)
            ->setSearchConfiguration(['sortField' => 'title', 'sortDirection' => 'desc'])
            ->getResults()
        ;

        $this->assertSame('B', $results['result'][0]->title);
    }

    #[Test]
    public function itSortsByFieldIncludedInAvailableSortFields(): void
    {
        Post::factory()->create(['title' => 'A']);
        Post::factory()->create(['title' => 'B']);

        $results = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableSortFields(['title'])
            ->setSearchConfiguration(['sortField' => 'title', 'sortDirection' => 'desc'])
            ->getResults()
        ;

        $this->assertSame('B', $results['result'][0]->title);
    }

    #[Test]
    public function itIgnoresSortFieldNotIncludedInAvailableSortFields(): void
    {
        Post::factory()->create(['title' => 'A']);
        Post::factory()->create(['title' => 'B']);

        $results = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableSortFields(['status'])
            ->setSearchConfiguration(['sortField' => 'title', 'sortDirection' => 'desc'])
            ->getResults()
        ;

        $this->assertSame('A', $results['result'][0]->title);
    }

    #[Test]
    public function itIgnoresAllSortFieldsWhenAvailableSortFieldsIsEmpty(): void
    {
        Post::factory()->create(['title' => 'A']);
        Post::factory()->create(['title' => 'B']);

        $service = new class extends ListService {
            protected string $listModel = Post::class;
            protected ?array $availableSortFields = [];
        };

        $results = $service
            ->setSearchConfiguration(['sortField' => 'title', 'sortDirection' => 'desc'])
            ->getResults()
        ;

        $this->assertSame('A', $results['result'][0]->title);
    }

    #[Test]
    public function itDoesNotWrapResultsWhenNoResourceIsConfigured(): void
    {
        Post::factory()->create();

        $results = (new ListService())->setListModel(Post::class)->getResults();

        $this->assertInstanceOf(Post::class, $results['result'][0]);
    }

    #[Test]
    public function itAppliesResourceToPaginatedResults(): void
    {
        Post::factory()->count(3)->create();

        $results = (new ListService())
            ->setListModel(Post::class)
            ->setListResource(LocalizedPostResource::class)
            ->setSearchConfigurationValue('perPage', 2)
            ->getResults()
        ;

        $this->assertEquals(3, $results['countItems']);
        $this->assertCount(2, $results['result']);
        $this->assertInstanceOf(LocalizedPostResource::class, $results['result'][0]);
    }

    #[Test]
    public function itAppliesResourceToNotPaginatedResults(): void
    {
        Post::factory()->count(3)->create();

        $results = (new ListService())
            ->setListModel(Post::class)
            ->setListResource(LocalizedPostResource::class)
            ->setPaginated(false)
            ->getResults()
        ;

        $this->assertCount(3, $results);
        $this->assertInstanceOf(LocalizedPostResource::class, $results[0]);
    }

    #[Test]
    public function itAppliesResourceConfiguredViaProperty(): void
    {
        Post::factory()->create();

        $service = new class extends ListService {
            protected string $listModel = Post::class;
            protected ?string $listResource = LocalizedPostResource::class;
        };

        $this->assertInstanceOf(LocalizedPostResource::class, $service->getResults()['result'][0]);
    }

    #[Test]
    public function itAppliesResourceToFindIncluding(): void
    {
        $post = Post::factory()->create();

        $element = (new ListService())
            ->setListModel(Post::class)
            ->setListResource(LocalizedPostResource::class)
            ->findIncluding($post->id)
        ;

        $this->assertInstanceOf(LocalizedPostResource::class, $element);
        $this->assertTrue($post->is($element->resource));
    }

    #[Test]
    public function itReturnsNullFromFindIncludingWhenElementDoesNotExistWithResource(): void
    {
        $element = (new ListService())
            ->setListModel(Post::class)
            ->setListResource(LocalizedPostResource::class)
            ->findIncluding(999)
        ;

        $this->assertNull($element);
    }

    #[Test]
    public function itReturnsPlainIdsWhenResourceIsConfigured(): void
    {
        $post = Post::factory()->create();

        $ids = (new ListService())
            ->setListModel(Post::class)
            ->setListResource(LocalizedPostResource::class)
            ->getAllIds()
        ;

        $this->assertSame([$post->id], $ids->all());
    }

    #[Test]
    public function itKeepsPaginationSettingAfterGettingAllIds(): void
    {
        Post::factory()->create();

        $service = (new ListService())->setListModel(Post::class);
        $service->getAllIds();

        $this->assertArrayHasKey('countItems', $service->getResults());
    }

    #[Test]
    #[DataProvider('invalidResourceClasses')]
    public function itThrowsExceptionWhenResourceClassIsNotValid(string $resourceClass): void
    {
        $this->expectException(InvalidResourceClassException::class);

        // DB vacía: el error de configuración se detecta aunque no haya registros
        (new ListService())
            ->setListModel(Post::class)
            ->setListResource($resourceClass)
            ->getResults()
        ;
    }

    public static function invalidResourceClasses(): array
    {
        return [
            'not a resource' => [Post::class],
            'resource collection' => [ResourceCollection::class],
            'unknown class' => ['App\Http\Resources\Unknown'],
        ];
    }

    #[Test]
    public function itThrowsExceptionWhenRequiredScopeHasNoRelationId(): void
    {
        Post::factory()->count(2)->create();

        $this->expectException(MissingRequiredScopeException::class);

        (new ListService())
            ->setListModel(Post::class)
            ->setRequiredScope('greaterThanId')
            ->setSearchConfiguration(['belongsTo' => 'greaterThanId'])
            ->getResults()
        ;
    }

    #[Test]
    public function itAppliesRequiredScopeNotListedInAvailableScopes(): void
    {
        Post::factory()->count(2)->create();

        $results = (new ListService())
            ->setListModel(Post::class)
            ->setRequiredScope('greaterThanId')
            ->setAvailableScopes(['similar'])
            ->setSearchConfiguration(['belongsTo' => 'greaterThanId', 'relationId' => 100])
            ->getResults()
        ;

        $this->assertEquals(0, $results['countItems']);
    }

    #[Test]
    public function itAppliesRequiredScopeWithDefaultAvailableScopes(): void
    {
        Post::factory()->count(2)->create();

        $results = (new ListService())
            ->setListModel(Post::class)
            ->setRequiredScope('greaterThanId')
            ->setSearchConfiguration(['belongsTo' => 'greaterThanId', 'relationId' => 100])
            ->getResults()
        ;

        $this->assertEquals(0, $results['countItems']);
    }

    #[Test]
    public function itThrowsExceptionGettingAllIdsWhenRequiredScopeIsMissing(): void
    {
        $this->expectException(MissingRequiredScopeException::class);

        (new ListService())
            ->setListModel(Post::class)
            ->setRequiredScope('greaterThanId')
            ->getAllIds()
        ;
    }

    #[Test]
    public function itThrowsExceptionWhenRequiredScopeIsMissing(): void
    {
        // Arrange
        Post::factory()->count(5)->create();

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setRequiredScope('greaterThanId')
            ->setSearchConfiguration([
                'perPage' => 10,
                'sortField' => 'id',
                'sortDirection' => 'asc',
                // Note: No 'belongsTo' set
            ])
        ;

        // Assert
        $this->expectException(MissingRequiredScopeException::class);

        // Act
        $service->getResults();
    }

    #[Test]
    public function itThrowsExceptionWhenRequiredScopeDoesNotMatch(): void
    {
        // Arrange
        Post::factory()->count(5)->create();

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setRequiredScope('greaterThanId')
            ->setSearchConfiguration([
                'perPage' => 10,
                'sortField' => 'id',
                'sortDirection' => 'asc',
                'belongsTo' => 'otherScope',  // Different scope
                'relationId' => 2,
            ])
        ;

        // Assert
        $this->expectException(MissingRequiredScopeException::class);

        // Act
        $service->getResults();
    }

    #[Test]
    public function itAppliesResultsWhenRequiredScopeIsProvided(): void
    {
        // Arrange
        Post::factory()->create(['id' => 1, 'title' => 'Post 1']);
        Post::factory()->create(['id' => 2, 'title' => 'Post 2']);
        Post::factory()->create(['id' => 3, 'title' => 'Post 3']);
        Post::factory()->create(['id' => 4, 'title' => 'Post 4']);
        Post::factory()->create(['id' => 5, 'title' => 'Post 5']);

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setRequiredScope('greaterThanId')
            ->setSearchConfiguration([
                'perPage' => 10,
                'sortField' => 'id',
                'sortDirection' => 'asc',
                'belongsTo' => 'greaterThanId',
                'relationId' => 2,
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
    public function itDoesNotValidateWhenRequiredScopeIsNull(): void
    {
        // Arrange
        Post::factory()->count(5)->create();

        $service = (new ListService())
            ->setListModel(Post::class)
            // Note: No setRequiredScope() call - defaults to null
            ->setSearchConfiguration([
                'perPage' => 10,
                'sortField' => 'id',
                'sortDirection' => 'asc',
                // Note: No 'belongsTo' set
            ])
        ;

        // Act
        $results = $service->getResults();

        // Assert - should not throw exception and return all results
        $this->assertEquals(5, $results['countItems']);
        $this->assertCount(5, $results['result']);
    }
}
