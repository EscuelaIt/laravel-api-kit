<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use EscuelaIT\APIKit\ListService;
use EscuelaIT\Test\Fixtures\Comment;
use EscuelaIT\Test\Fixtures\Post;
use EscuelaIT\Test\Fixtures\PostWithTraitMethods;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 *
 * @coversNothing
 */
class ListServiceIncludeGuardTest extends TestCase
{
    #[Test]
    #[DataProvider('notRelationIncludes')]
    public function itIgnoresIncludesThatAreNotRelationsAndDoesNotCallThem(string $include): void
    {
        $post = Post::factory()->create();
        Comment::factory()->create(['post_id' => $post->id]);

        $results = (new ListService())
            ->setListModel(PostWithTraitMethods::class)
            ->setAvailableIncludes(null)
            ->setSearchConfiguration(['include' => $include])
            ->getResults()
        ;

        $this->assertSame(1, Post::query()->count());
        $this->assertSame(1, Comment::query()->count());
        $this->assertCount(1, $results['result']);
        $this->assertSame([], $results['result'][0]->getRelations());
    }

    public static function notRelationIncludes(): array
    {
        return [
            'unknown' => ['nope'],
            'forwarded to query builder' => ['truncate'],
            'forwarded to eloquent builder' => ['forceDelete'],
            'base model method' => ['delete'],
            'typed method that is not a relation' => ['typedWipe'],
            'untyped method from a trait' => ['untypedWipe'],
            'untyped method in the model' => ['untypedOwnWipe'],
            'untyped relation' => ['untypedComments'],
            'untyped method nested' => ['comments.post.untypedOwnWipe'],
            'static method' => ['factory'],
            'protected method' => ['newFactory'],
            'unknown nested' => ['comments.nope'],
            'forwarded nested' => ['comments.truncate'],
        ];
    }

    #[Test]
    public function itDoesNotRunForwardedMethodsInFindIncluding(): void
    {
        $post = Post::factory()->create();

        $element = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableIncludes(null)
            ->setSearchConfiguration(['include' => 'truncate'])
            ->findIncluding($post->id)
        ;

        $this->assertTrue($post->is($element));
        $this->assertSame([], $element->getRelations());
        $this->assertSame(1, Post::query()->count());
    }

    #[Test]
    #[DataProvider('relationIncludes')]
    public function itLoadsIncludesThatAreRelations(string $include, string $relation): void
    {
        Post::factory()->create();

        $results = (new ListService())
            ->setListModel(PostWithTraitMethods::class)
            ->setAvailableIncludes(null)
            ->setSearchConfiguration(['include' => $include])
            ->getResults()
        ;

        $this->assertTrue($results['result'][0]->relationLoaded($relation));
    }

    public static function relationIncludes(): array
    {
        return [
            'typed in the model' => ['comments', 'comments'],
            'typed in a trait' => ['typedComments', 'typedComments'],
            'nested' => ['comments.post', 'comments'],
            'with columns' => ['comments:id', 'comments'],
        ];
    }

    #[Test]
    public function itLoadsIncludesDefinedWithResolveRelationUsing(): void
    {
        Post::factory()->create();
        Post::resolveRelationUsing('resolvedComments', fn ($post) => $post->hasMany(Comment::class, 'post_id'));

        $results = (new ListService())
            ->setListModel(Post::class)
            ->setAvailableIncludes(null)
            ->setSearchConfiguration(['include' => ['resolvedComments']])
            ->getResults()
        ;

        $this->assertTrue($results['result'][0]->relationLoaded('resolvedComments'));
    }

    #[Test]
    public function itLoadsUntypedRelationListedInAvailableIncludes(): void
    {
        Post::factory()->create();

        $results = (new ListService())
            ->setListModel(PostWithTraitMethods::class)
            ->setAvailableIncludes(['untypedComments'])
            ->setSearchConfiguration(['include' => 'untypedComments'])
            ->getResults()
        ;

        $this->assertTrue($results['result'][0]->relationLoaded('untypedComments'));
    }
}
