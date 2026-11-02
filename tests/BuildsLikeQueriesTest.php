<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use EscuelaIT\APIKit\ListService;
use EscuelaIT\Test\Filters\TitleContainsFilter;
use EscuelaIT\Test\Fixtures\Post;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 *
 * @coversNothing
 */
class BuildsLikeQueriesTest extends TestCase
{
    #[Test]
    #[DataProvider('containsSearches')]
    public function itFindsValuesContainingTheText(mixed $value, array $expected): void
    {
        foreach (['100% real', '100 real', 'a_b', 'axb', 'back\slash', 'Laravel API', 'true story'] as $title) {
            Post::factory()->create(['title' => $title]);
        }

        $titles = Post::query()->similar($value)->orderBy('id')->pluck('title')->all();

        $this->assertSame($expected, $titles);
    }

    public static function containsSearches(): array
    {
        $all = ['100% real', '100 real', 'a_b', 'axb', 'back\slash', 'Laravel API', 'true story'];

        return [
            'text' => ['real', ['100% real', '100 real']],
            'different case' => ['laravel', ['Laravel API']],
            'percent is literal' => ['100%', ['100% real']],
            'only a percent' => ['%', ['100% real']],
            'underscore is literal' => ['a_b', ['a_b']],
            'backslash is literal' => ['\\', ['back\slash']],
            'boolean from a filter' => [true, ['true story']],
            'number' => [100, ['100% real', '100 real']],
            'no matches' => ['nothing', []],
            'empty string does not constrain' => ['', $all],
            'null does not constrain' => [null, $all],
            'array does not constrain' => [['real'], $all],
        ];
    }

    #[Test]
    public function itFindsValuesStartingWithTheText(): void
    {
        Post::factory()->create(['title' => 'Laravel API']);
        Post::factory()->create(['title' => 'API Laravel']);
        Post::factory()->create(['title' => 'La% otra']);

        $this->assertSame(['Laravel API'], Post::query()->titleStartsWith('Laravel')->pluck('title')->all());
        $this->assertSame(['La% otra'], Post::query()->titleStartsWith('La%')->pluck('title')->all());
    }

    #[Test]
    public function itSearchesInAnyOfTheColumns(): void
    {
        Post::factory()->create(['title' => 'match', 'status' => 'draft']);
        Post::factory()->create(['title' => 'other', 'status' => 'match']);
        Post::factory()->create(['title' => 'other', 'status' => 'draft']);

        $this->assertSame(2, Post::query()->similar('match')->count());
    }

    #[Test]
    public function itDoesNotEscapeTheConstraintsOfTheQuery(): void
    {
        Post::factory()->create(['title' => 'match', 'status' => 'published']);
        Post::factory()->create(['title' => 'match', 'status' => 'draft']);

        $service = new class extends ListService {
            protected string $listModel = Post::class;

            protected function createQuery()
            {
                return parent::createQuery()->where('status', 'published');
            }

            protected function applyKeywordFilter(?string $keyword): void
            {
                $this->whereContains($this->query, ['title', 'status'], $keyword);
            }
        };

        $results = $service->setSearchConfiguration(['keyword' => 'match'])->getResults();

        $this->assertEquals(1, $results['countItems']);
    }

    #[Test]
    #[DataProvider('filterValues')]
    public function itWorksInCustomFilters(mixed $value, int $expected): void
    {
        Post::factory()->create(['title' => '100% real']);
        Post::factory()->create(['title' => '100 real']);
        Post::factory()->create(['title' => 'true story']);

        $service = new class extends ListService {
            protected string $listModel = Post::class;

            protected function customFilters(): array
            {
                return [new TitleContainsFilter()];
            }
        };

        $results = $service
            ->setSearchConfiguration(['filters' => [['name' => 'title_contains', 'active' => 'true', 'value' => $value]]])
            ->getResults()
        ;

        $this->assertEquals($expected, $results['countItems']);
    }

    public static function filterValues(): array
    {
        return [
            'text' => ['real', 2],
            'wildcard is literal' => ['100%', 1],
            'the word true' => ['true', 1],
            'empty does not constrain' => ['', 3],
        ];
    }
}
