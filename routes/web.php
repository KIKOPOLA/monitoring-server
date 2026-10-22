<?php

use Illuminate\Support\Facades\Route;
use Kikopola\MonitoringServer\Http\Controllers\MonitoringDashboardController;

if (config('monitoring-server.enabled', true)) {
    $dashboardPath = config('monitoring-server.dashboard_path', 'monitoring');
    $middleware = config('monitoring-server.middleware', ['web']);

    if (is_string($middleware)) {
        $middleware = array_map('trim', explode(',', $middleware));
    }

    if (config('monitoring-server.require_auth', false) && !in_array('auth', $middleware)) {
        $middleware[] = 'auth';
    }

    // Dashboard login & logout routes (needs 'web' middleware for sessions & CSRF)
    Route::middleware(['web'])->group(function () use ($dashboardPath) {
        Route::post($dashboardPath . '/login', [MonitoringDashboardController::class, 'login'])->name('monitoring-server.login');
        Route::match(['GET', 'POST'], $dashboardPath . '/logout', [MonitoringDashboardController::class, 'logout'])->name('monitoring-server.logout');
    });

    // Main dashboard view route
    Route::middleware($middleware)->group(function () use ($dashboardPath) {
        Route::get($dashboardPath, [MonitoringDashboardController::class, 'index'])->name('monitoring-server.dashboard');
    });
}
