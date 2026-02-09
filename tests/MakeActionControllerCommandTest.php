<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use Illuminate\Filesystem\Filesystem;

/**
 * @internal
 *
 * @coversNothing
 */
class MakeActionControllerCommandTest extends TestCase
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

    public function testItCreatesAnActionControllerWithoutService(): void
    {
        $this->artisan('make:api-action-controller', ['name' => 'ActionUserController'])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Http/Controllers/ActionUserController.php');
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('namespace App\Http\Controllers;', $contents);
        $this->assertStringContainsString('use App\Http\Controllers\Controller;', $contents);
        $this->assertStringContainsString('use EscuelaIT\APIKit\ActionHandler;', $contents);
        $this->assertStringContainsString('class ActionUserController extends Controller', $contents);
        $this->assertStringContainsString('use ActionHandler;', $contents);
        $this->assertStringContainsString('public function handle(UserActionService $service)', $contents);
        $this->assertStringContainsString('return $this->handleAction($service);', $contents);
    }

    public function testItAutoGeneratesServiceName(): void
    {
        $this->artisan('make:api-action-controller', ['name' => 'ActionUserController'])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Http/Controllers/ActionUserController.php');
        $contents = file_get_contents($file);

        // ActionUserController debería usar UserActionService
        $this->assertStringContainsString('use App\Services\UserActionService;', $contents);
        $this->assertStringContainsString('public function handle(UserActionService $service)', $contents);
    }

    public function testItCreatesAnActionControllerWithCustomService(): void
    {
        $this->artisan('make:api-action-controller', [
            'name' => 'ActionUserController',
            'service' => 'User\UserActionService',
        ])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Http/Controllers/ActionUserController.php');
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('use App\Services\User\UserActionService;', $contents);
        $this->assertStringContainsString('public function handle(UserActionService $service)', $contents);
    }

    public function testItCreatesAnActionControllerWithFullServicePath(): void
    {
        $this->artisan('make:api-action-controller', [
            'name' => 'ActionPostController',
            'service' => 'App\Services\Post\PostActionService',
        ])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Http/Controllers/ActionPostController.php');
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('use App\Services\Post\PostActionService;', $contents);
        $this->assertStringContainsString('public function handle(PostActionService $service)', $contents);
    }

    public function testItPlacesFileInHttpControllersNamespace(): void
    {
        $this->artisan('make:api-action-controller', ['name' => 'ActionItemController'])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Http/Controllers/ActionItemController.php');
        $this->assertFileExists($file);
    }

    public function testItIncludesActionHandlerTrait(): void
    {
        $this->artisan('make:api-action-controller', ['name' => 'ActionProductController'])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Http/Controllers/ActionProductController.php');
        $contents = file_get_contents($file);

        $this->assertStringContainsString('use ActionHandler;', $contents);
    }

    public function testItHasHandleMethod(): void
    {
        $this->artisan('make:api-action-controller', ['name' => 'ActionCategoryController'])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Http/Controllers/ActionCategoryController.php');
        $contents = file_get_contents($file);

        $this->assertStringContainsString('public function handle(CategoryActionService $service)', $contents);
        $this->assertStringContainsString('return $this->handleAction($service);', $contents);
    }
}
