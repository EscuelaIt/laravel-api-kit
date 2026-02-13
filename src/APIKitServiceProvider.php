<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit;

use EscuelaIT\APIKit\Console\Commands\MakeActionCommand;
use EscuelaIT\APIKit\Console\Commands\MakeActionControllerCommand;
use EscuelaIT\APIKit\Console\Commands\MakeActionServiceCommand;
use EscuelaIT\APIKit\Console\Commands\MakeListControllerCommand;
use EscuelaIT\APIKit\Console\Commands\MakeListServiceCommand;
use Illuminate\Support\ServiceProvider;

class APIKitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeActionCommand::class,
                MakeListServiceCommand::class,
                MakeActionServiceCommand::class,
                MakeListControllerCommand::class,
                MakeActionControllerCommand::class,
            ]);
            $this->publishes([
                __DIR__.'/Console/Commands/stubs' => base_path('stubs/vendor/api-kit'),
            ], 'api-kit-stubs');
        }
    }

    public function register(): void
    {
        // ...
    }
}
