<?php

return [
    /**
     * Enable or disable monitoring package entirely.
     */
    'enabled' => env('MONITORING_SERVER_ENABLED', true),

    /**
     * Web dashboard URL path. Default: /monitoring
     */
    'dashboard_path' => env('MONITORING_SERVER_DASHBOARD_PATH', 'monitoring'),

    /**
     * API endpoint URL prefix. Default: /api/monitoring
     */
    'api_prefix' => env('MONITORING_SERVER_API_PREFIX', 'api/monitoring'),

    /**
     * Optional secret token or key to protect monitoring endpoints.
     * When set, dashboard and API require ?token=... or Header X-Monitoring-Token.
     */
    'api_token' => env('MONITORING_SERVER_API_TOKEN', null),

    /**
     * Dedicated localhost port for the monitoring dashboard server.
     */
    'port' => (int) env('MONITORING_SERVER_PORT', 8989),

    /**
     * Middleware applied to web dashboard routes.
     */
    'middleware' => ['web'],

    /**
     * Middleware applied to API routes (default: throttle to prevent DoS attacks).
     */
    'api_middleware' => ['throttle:60,1'],

    /**
     * Enable or disable CORS headers for external/central dashboard integrations.
     */
    'cors' => env('MONITORING_SERVER_CORS', true),
];
