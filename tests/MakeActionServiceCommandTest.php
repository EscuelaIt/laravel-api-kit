<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use Illuminate\Filesystem\Filesystem;

/**
 * @internal
 *
 * @coversNothing
 */
class MakeActionServiceCommandTest extends TestCase
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

    public function testItCreatesAnActionServiceWithoutModel(): void
    {
        $this->artisan('make:api-action-service', ['name' => 'UserActionService'])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Services/UserActionService.php');
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('namespace App\Services;', $contents);
        $this->assertStringContainsString('class UserActionService extends ActionService', $contents);
        $this->assertStringContainsString('protected string $actionModel;', $contents);
        $this->assertStringContainsString('protected array $actionTypes = [];', $contents);
        $this->assertStringNotContainsString('use App\Models', $contents);
    }

    public function testItCreatesAnActionServiceWithModel(): void
    {
        $this->artisan('make:api-action-service', [
            'name' => 'UserActionService',
            'model' => 'User',
        ])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Services/UserActionService.php');
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('namespace App\Services;', $contents);
        $this->assertStringContainsString('use App\Models\User;', $contents);
        $this->assertStringContainsString('class UserActionService extends ActionService', $contents);
        $this->assertStringContainsString('protected string $actionModel = User::class;', $contents);
        $this->assertStringContainsString('protected array $actionTypes = [];', $contents);
        $this->assertStringContainsString('use EscuelaIT\APIKit\ActionService;', $contents);
    }

    public function testItCreatesAnActionServiceWithFullModelPath(): void
    {
        $this->artisan('make:api-action-service', [
            'name' => 'PostActionService',
            'model' => 'App\Models\Post',
        ])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Services/PostActionService.php');
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('use App\Models\Post;', $contents);
        $this->assertStringContainsString('protected string $actionModel = Post::class;', $contents);
    }

    public function testItPlacesFileInServicesNamespace(): void
    {
        $this->artisan('make:api-action-service', ['name' => 'TestActionService'])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Services/TestActionService.php');
        $this->assertFileExists($file);
    }

    public function testItIncludesActionTypesArray(): void
    {
        $this->artisan('make:api-action-service', ['name' => 'UserActionService'])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Services/UserActionService.php');
        $contents = file_get_contents($file);

        $this->assertStringContainsString('protected array $actionTypes = [];', $contents);
    }
}
