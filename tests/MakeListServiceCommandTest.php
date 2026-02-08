<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use Illuminate\Filesystem\Filesystem;

/**
 * @internal
 *
 * @coversNothing
 */
class MakeListServiceCommandTest extends TestCase
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
        $appPath = $this->app->basePath('app/Services');

        if ($this->files->exists($appPath)) {
            $this->files->deleteDirectory($appPath);
        }

        parent::tearDown();
    }

    public function test_it_creates_a_list_service_without_model(): void
    {
        $this->artisan('make:api-list-service', ['name' => 'UserListService'])
            ->assertExitCode(0);

        $file = $this->app->basePath('app/Services/UserListService.php');
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('namespace App\Services;', $contents);
        $this->assertStringContainsString('class UserListService extends ListService', $contents);
        $this->assertStringContainsString('protected string $listModel;', $contents);
        $this->assertStringNotContainsString('use App\Models', $contents);
    }

    public function test_it_creates_a_list_service_with_model(): void
    {
        $this->artisan('make:api-list-service', [
            'name' => 'UserListService',
            'model' => 'User',
        ])
            ->assertExitCode(0);

        $file = $this->app->basePath('app/Services/UserListService.php');
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('namespace App\Services;', $contents);
        $this->assertStringContainsString('use App\Models\User;', $contents);
        $this->assertStringContainsString('class UserListService extends ListService', $contents);
        $this->assertStringContainsString('protected string $listModel = User::class;', $contents);
        $this->assertStringContainsString('use EscuelaIT\APIKit\ListService;', $contents);
    }

    public function test_it_creates_a_list_service_with_full_model_path(): void
    {
        $this->artisan('make:api-list-service', [
            'name' => 'PostListService',
            'model' => 'App\Models\Post',
        ])
            ->assertExitCode(0);

        $file = $this->app->basePath('app/Services/PostListService.php');
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('use App\Models\Post;', $contents);
        $this->assertStringContainsString('protected string $listModel = Post::class;', $contents);
    }

    public function test_it_places_file_in_services_namespace(): void
    {
        $this->artisan('make:api-list-service', ['name' => 'TestListService'])
            ->assertExitCode(0);

        $file = $this->app->basePath('app/Services/TestListService.php');
        $this->assertFileExists($file);
    }
}
