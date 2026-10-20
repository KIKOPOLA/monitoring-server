<?php

use Illuminate\Support\Facades\Route;
use Kikopola\MonitoringServer\Http\Controllers\MonitoringDashboardController;

if (config('monitoring-server.enabled', true)) {
    $apiPrefix = config('monitoring-server.api_prefix', 'api/monitoring');
    $middleware = config('monitoring-server.api_middleware', ['throttle:60,1']);

    Route::prefix($apiPrefix)->middleware($middleware)->group(function () {
        Route::get('/metrics', [MonitoringDashboardController::class, 'api'])->name('monitoring-server.api.metrics');
    });
}
