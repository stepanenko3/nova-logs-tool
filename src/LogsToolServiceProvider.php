<?php

namespace Stepanenko3\LogsTool;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Stepanenko3\LogsTool\Http\Middleware\Authorize;
use Laravel\Nova\Nova;

class LogsToolServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        // Register nova routes
        Nova::router()->group(function ($router): void {
            $path = 'logs';
            $router->get($path, fn () => inertia('NovaLogs', ['basePath' => $path]));
        });

        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['nova', 'nova:api', Authorize::class])
            ->prefix('nova-vendor/stepanenko3/logs-tool')
            ->group(__DIR__ . '/../routes/api.php');
    }
}
