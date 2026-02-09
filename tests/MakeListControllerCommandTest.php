<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use Illuminate\Filesystem\Filesystem;

/**
 * @internal
 *
 * @coversNothing
 */
class MakeListControllerCommandTest extends TestCase
{
    private Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();
        $this->files = new Filesystem();
    }

    protected function tearDown(): void
    {
        // Limpiar archivos generados
        $appPath = $this->app->basePath('app/Http/Controllers');

        if ($this->files->exists($appPath)) {
            $this->files->deleteDirectory($appPath);
        }

        parent::tearDown();
    }

    public function testItCreatesAListControllerWithoutService(): void
    {
        $this->artisan('make:api-list-controller', ['name' => 'ListUserController'])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Http/Controllers/ListUserController.php');
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('namespace App\Http\Controllers;', $contents);
        $this->assertStringContainsString('use App\Http\Controllers\Controller;', $contents);
        $this->assertStringContainsString('use EscuelaIT\APIKit\ResourceListable;', $contents);
        $this->assertStringContainsString('class ListUserController extends Controller', $contents);
        $this->assertStringContainsString('use ResourceListable;', $contents);
        $this->assertStringContainsString('public function index(UserListService $service)', $contents);
        $this->assertStringContainsString('public function ids(UserListService $service)', $contents);
        $this->assertStringContainsString('return $this->list($service);', $contents);
        $this->assertStringContainsString('return $this->allIds($service);', $contents);
    }

    public function testItAutoGeneratesServiceName(): void
    {
        $this->artisan('make:api-list-controller', ['name' => 'ListUserController'])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Http/Controllers/ListUserController.php');
        $contents = file_get_contents($file);

        // ListUserController debería usar UserListService
        $this->assertStringContainsString('use App\Services\UserListService;', $contents);
        $this->assertStringContainsString('public function index(UserListService $service)', $contents);
    }

    public function testItCreatesAListControllerWithCustomService(): void
    {
        $this->artisan('make:api-list-controller', [
            'name' => 'ListUserController',
            'service' => 'User\UserListService',
        ])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Http/Controllers/ListUserController.php');
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('use App\Services\User\UserListService;', $contents);
        $this->assertStringContainsString('public function index(UserListService $service)', $contents);
    }

    public function testItCreatesAListControllerWithFullServicePath(): void
    {
        $this->artisan('make:api-list-controller', [
            'name' => 'ListPostController',
            'service' => 'App\Services\Post\PostListService',
        ])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Http/Controllers/ListPostController.php');
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('use App\Services\Post\PostListService;', $contents);
        $this->assertStringContainsString('public function index(PostListService $service)', $contents);
    }

    public function testItPlacesFileInHttpControllersNamespace(): void
    {
        $this->artisan('make:api-list-controller', ['name' => 'ListItemController'])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Http/Controllers/ListItemController.php');
        $this->assertFileExists($file);
    }

    public function testItIncludesResourceListableTrait(): void
    {
        $this->artisan('make:api-list-controller', ['name' => 'ListProductController'])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Http/Controllers/ListProductController.php');
        $contents = file_get_contents($file);

        $this->assertStringContainsString('use ResourceListable;', $contents);
    }
}
