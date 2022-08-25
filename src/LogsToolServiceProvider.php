<?php

namespace Stepanenko3\LogsTool;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Stepanenko3\LogsTool\Http\Middleware\Authorize;
use Laravel\Nova\Nova;

class LogsToolServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerRoutes();
    }

    /**
     * Register the tool's routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        // Register nova routes
        Nova::router()->group(function ($router) {
            $path = 'logs';
            $router->get($path, fn () => inertia('NovaLogs', ['basePath' => $path]));
        });

        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['nova', Authorize::class])
            ->prefix('nova-vendor/stepanenko3/logs-tool')
            ->group(__DIR__ . '/../routes/api.php');
    }
}
