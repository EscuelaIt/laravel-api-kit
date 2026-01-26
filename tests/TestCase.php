<?php

namespace EscuelaIT\Test;

use EscuelaIT\APIKit\APIKitServiceProvider; // tu ServiceProvider
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use RefreshDatabase; // refresca DB después de cada test

    protected function getPackageProviders($app)
    {
        return [
            APIKitServiceProvider::class,
            \Negartarh\APIWrapper\APIResponseServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Configura DB de testing (SQLite in-memory)
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/migrations');
    }
}
