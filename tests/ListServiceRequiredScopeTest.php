<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use EscuelaIT\APIKit\Exceptions\MissingRequiredScopeException;
use EscuelaIT\APIKit\ListService;
use EscuelaIT\Test\Fixtures\Post;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 *
 * @coversNothing
 */
class ListServiceRequiredScopeTest extends TestCase
{
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
    public function itThrowsExceptionInFindIncludingWhenRequiredScopeIsMissing(): void
    {
        $post = Post::factory()->create();

        $this->expectException(MissingRequiredScopeException::class);

        (new ListService())
            ->setListModel(Post::class)
            ->setRequiredScope('greaterThanId')
            ->findIncluding($post->id)
        ;
    }

    #[Test]
    public function itThrowsExceptionInFindIncludingWhenRequiredScopeHasNoRelationId(): void
    {
        $post = Post::factory()->create();

        $this->expectException(MissingRequiredScopeException::class);

        (new ListService())
            ->setListModel(Post::class)
            ->setRequiredScope('greaterThanId')
            ->setSearchConfiguration(['belongsTo' => 'greaterThanId'])
            ->findIncluding($post->id)
        ;
    }

    #[Test]
    public function itDoesNotFindElementOutsideRequiredScope(): void
    {
        Post::factory()->create(['id' => 1]);
        Post::factory()->create(['id' => 5]);

        $service = (new ListService())
            ->setListModel(Post::class)
            ->setRequiredScope('greaterThanId')
            ->setSearchConfiguration(['belongsTo' => 'greaterThanId', 'relationId' => 2])
        ;

        $this->assertNull($service->findIncluding(1));
        $this->assertSame(5, $service->findIncluding(5)->id);
    }

    #[Test]
    public function itIgnoresBelongsToInFindIncludingWithoutRequiredScope(): void
    {
        Post::factory()->create(['id' => 1]);

        $element = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableScopes(['greaterThanId'])
            ->setSearchConfiguration(['belongsTo' => 'greaterThanId', 'relationId' => 2])
            ->findIncluding(1)
        ;

        $this->assertSame(1, $element->id);
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
}
