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
     * Standalone password protection for the monitoring dashboard.
     * When set, visitors must enter this password to view the dashboard (no DB or registration needed).
     * Example: 'bintang123'
     */
    'password' => env('MONITORING_SERVER_PASSWORD', env('MONITORING_PASSWORD', null)),

    /**
     * Optional username if you want username + password login authentication.
     */
    'username' => env('MONITORING_SERVER_USERNAME', null),

    /**
     * Require authentication before accessing the dashboard.
     * When true, unauthenticated visitors are automatically redirected to the application's /login page.
     */
    'require_auth' => env('MONITORING_SERVER_REQUIRE_AUTH', false),

    /**
     * Middleware applied to web dashboard routes.
     * Can also be customized via .env (e.g. MONITORING_SERVER_MIDDLEWARE=web,auth)
     */
    'middleware' => env('MONITORING_SERVER_MIDDLEWARE')
        ? array_map('trim', explode(',', env('MONITORING_SERVER_MIDDLEWARE')))
        : ['web'],

    /**
     * Middleware applied to API routes (default: throttle to prevent DoS attacks).
     */
    'api_middleware' => ['throttle:60,1'],

    /**
     * Enable or disable CORS headers for external/central dashboard integrations.
     */
    'cors' => env('MONITORING_SERVER_CORS', true),

    /**
     * Central Master Monitoring Hub integration (PUSH / Heartbeat).
     * Set this URL to automatically send server health and metrics to a central dashboard.
     * Example: 'http://localhost:8000/api/monitor/heartbeat'
     */
    'hub_url' => env('MONITORING_SERVER_HUB_URL', null),

    /**
     * Optional secret bearer token to authenticate with the central hub.
     */
    'hub_token' => env('MONITORING_SERVER_HUB_TOKEN', null),

    /**
     * Application identity reported to the central hub.
     */
    'app_name' => env('MONITORING_SERVER_APP_NAME', env('APP_NAME', 'Laravel')),
    'app_url' => env('MONITORING_SERVER_APP_URL', env('APP_URL', 'http://localhost')),

    /**
     * Enable automatic background scheduling for heartbeat reports.
     * Frequency options: everyMinute, everyTwoMinutes, everyFiveMinutes, everyTenMinutes, etc.
     */
    'hub_schedule_enabled' => env('MONITORING_SERVER_HUB_SCHEDULE', false),
    'hub_frequency' => env('MONITORING_SERVER_HUB_FREQUENCY', 'everyFiveMinutes'),
];
