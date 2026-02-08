<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit;

use EscuelaIT\APIKit\Console\Commands\MakeActionCommand;
use EscuelaIT\APIKit\Console\Commands\MakeActionServiceCommand;
use EscuelaIT\APIKit\Console\Commands\MakeListServiceCommand;
use EscuelaIT\APIKit\View\Components\CrudList;
use Illuminate\Support\ServiceProvider;

class APIKitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'api-kit');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeActionCommand::class,
                MakeListServiceCommand::class,
                MakeActionServiceCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/Console/Commands/stubs' => base_path('stubs/vendor/api-kit'),
            ], 'api-kit-stubs');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/api-kit'),
            ], 'api-kit-views');

            $this->publishes([
                __DIR__.'/../resources/assets' => resource_path('vendor/api-kit'),
            ], 'api-kit-assets');
        }

        // Register Blade components
        $this->loadViewComponentsAs('api-kit', [
            CrudList::class,
        ]);
    }

    public function register(): void
    {
        // ...
    }
}
