<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit;

use EscuelaIT\APIKit\View\Components\CrudList;
use Illuminate\Support\ServiceProvider;

class APIKitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'api-kit');

        if ($this->app->runningInConsole()) {
            // Publish views
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
