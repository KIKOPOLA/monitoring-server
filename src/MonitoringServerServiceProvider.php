<?php

namespace Kikopola\MonitoringServer;

use Illuminate\Support\ServiceProvider;
use Kikopola\MonitoringServer\Commands\CheckServerCommand;
use Kikopola\MonitoringServer\Commands\ServeServerCommand;
use Kikopola\MonitoringServer\Services\ServerMonitor;

class MonitoringServerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/monitoring-server.php',
            'monitoring-server'
        );

        $this->app->singleton(ServerMonitor::class, function () {
            return new ServerMonitor();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load web and API routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Load Blade views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'monitoring-server');

        // Register artisan commands and publishable assets
        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckServerCommand::class,
                ServeServerCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/monitoring-server.php' => config_path('monitoring-server.php'),
            ], 'monitoring-server-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/monitoring-server'),
            ], 'monitoring-server-views');
        }
    }
}
