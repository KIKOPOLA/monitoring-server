<?php

namespace Kikopola\MonitoringServer;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Kikopola\MonitoringServer\Commands\CheckServerCommand;
use Kikopola\MonitoringServer\Commands\HeartbeatCommand;
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
                HeartbeatCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/monitoring-server.php' => config_path('monitoring-server.php'),
            ], 'monitoring-server-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/monitoring-server'),
            ], 'monitoring-server-views');

            // Optional auto-schedule for heartbeat reports to Central Hub
            $this->app->booted(function () {
                if ($this->app->bound(Schedule::class)) {
                    $schedule = $this->app->make(Schedule::class);
                    if (config('monitoring-server.hub_schedule_enabled', false) && !empty(config('monitoring-server.hub_url'))) {
                        $freq = config('monitoring-server.hub_frequency', 'everyFiveMinutes');
                        $cmd = $schedule->command('monitoring:heartbeat');
                        if (method_exists($cmd, $freq)) {
                            $cmd->$freq()->withoutOverlapping();
                        } else {
                            $cmd->everyFiveMinutes()->withoutOverlapping();
                        }
                    }
                }
            });
        }
    }
}
