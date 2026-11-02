<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use EscuelaIT\APIKit\ListService;
use EscuelaIT\APIKit\ResourceListable;
use EscuelaIT\Test\Fixtures\Post;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 *
 * @coversNothing
 */
class MissingRequiredScopeResponseTest extends TestCase
{
    #[Test]
    #[DataProvider('methods')]
    public function itRespondsForbiddenWhenRequiredScopeIsMissing(string $method): void
    {
        Post::factory()->create();

        Route::get('/required-scope', function () use ($method) {
            $controller = new class {
                use ResourceListable;
            };

            return $controller->{$method}((new ListService())->setListModel(Post::class)->setRequiredScope('greaterThanId'));
        });

        $response = $this->getJson('/required-scope');

        $response->assertStatus(403);
        $response->assertJsonPath('status', 403);
        $response->assertJsonPath('message', 'Missing required scope: greaterThanId');
        $response->assertJsonMissingPath('data.result');
    }

    public static function methods(): array
    {
        return [
            'list' => ['list'],
            'allIds' => ['allIds'],
        ];
    }
}
