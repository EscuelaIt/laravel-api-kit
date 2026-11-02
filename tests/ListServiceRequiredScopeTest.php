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
