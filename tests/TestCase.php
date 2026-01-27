<?php

declare(strict_types=1);

namespace EscuelaIT\Test;

use EscuelaIT\APIKit\APIKitServiceProvider; // tu ServiceProvider
use Illuminate\Foundation\Testing\RefreshDatabase;
use Negartarh\APIWrapper\APIResponseServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * @internal
 *
 * @coversNothing
 */
class TestCase extends Orchestra
{
    use RefreshDatabase; // refresca DB después de cada test

    protected function getPackageProviders($app)
    {
        return [
            APIKitServiceProvider::class,
            APIResponseServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Configura DB de testing (SQLite in-memory)
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/migrations');
    }
}
