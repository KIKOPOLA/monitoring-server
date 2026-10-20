<?php

use Illuminate\Support\Facades\Route;
use Kikopola\MonitoringServer\Http\Controllers\MonitoringDashboardController;

if (config('monitoring-server.enabled', true)) {
    $dashboardPath = config('monitoring-server.dashboard_path', 'monitoring');
    $middleware = config('monitoring-server.middleware', ['web']);

    Route::middleware($middleware)->group(function () use ($dashboardPath) {
        Route::get($dashboardPath, [MonitoringDashboardController::class, 'index'])->name('monitoring-server.dashboard');
    });
}
