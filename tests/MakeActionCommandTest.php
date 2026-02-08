<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use Illuminate\Filesystem\Filesystem;

/**
 * @internal
 *
 * @coversNothing
 */
class MakeActionCommandTest extends TestCase
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
        $appPath = $this->app->basePath('app/Actions');

        if ($this->files->exists($appPath)) {
            $this->files->deleteDirectory($appPath);
        }

        parent::tearDown();
    }

    public function testItCreatesAnActionClass(): void
    {
        $this->artisan('make:api-action', ['name' => 'CreateUserAction'])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Actions/CreateUserAction.php');
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('namespace App\Actions;', $contents);
        $this->assertStringContainsString('use EscuelaIT\APIKit\CrudAction;', $contents);
        $this->assertStringContainsString('use EscuelaIT\APIKit\ActionResult;', $contents);
        $this->assertStringContainsString('class CreateUserAction extends CrudAction', $contents);
        $this->assertStringContainsString('protected function validationRules(): array', $contents);
        $this->assertStringContainsString('public function handle(): ActionResult', $contents);
    }

    public function testItPlacesFileInActionsNamespace(): void
    {
        $this->artisan('make:api-action', ['name' => 'DeletePostAction'])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Actions/DeletePostAction.php');
        $this->assertFileExists($file);
    }

    public function testItCreatesActionWithNestedNamespace(): void
    {
        $this->artisan('make:api-action', ['name' => 'Admin/ManageUsersAction'])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Actions/Admin/ManageUsersAction.php');
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('namespace App\Actions\Admin;', $contents);
        $this->assertStringContainsString('class ManageUsersAction extends CrudAction', $contents);
    }

    public function testItHasCorrectStructure(): void
    {
        $this->artisan('make:api-action', ['name' => 'TestAction'])
            ->assertExitCode(0)
        ;

        $file = $this->app->basePath('app/Actions/TestAction.php');
        $contents = file_get_contents($file);

        $this->assertStringContainsString('validationRules(): array', $contents);
        $this->assertStringContainsString('handle(): ActionResult', $contents);
        $this->assertStringContainsString('foreach($this->models as $model)', $contents);
    }
}
