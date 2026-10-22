<?php

namespace Kikopola\MonitoringServer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kikopola\MonitoringServer\Services\ServerMonitor;

class MonitoringDashboardController extends Controller
{
    /**
     * Show the server monitoring web dashboard.
     */
    public function index(Request $request, ServerMonitor $monitor)
    {
        $this->authorizeAccess($request);

        $metrics = $monitor->getAllMetrics();
        $apiPrefix = config('monitoring-server.api_prefix', 'api/monitoring');
        $apiEndpoint = url(trim($apiPrefix, '/') . '/metrics');

        return view('monitoring-server::dashboard', [
            'metrics' => $metrics,
            'apiEndpoint' => $apiEndpoint,
            'token' => $request->query('token') ?: $request->header('X-Monitoring-Token'),
        ]);
    }

    /**
     * Return real-time metrics in JSON format for live AJAX polling.
     */
    public function api(Request $request, ServerMonitor $monitor): JsonResponse
    {
        $headers = $this->corsHeaders();

        if ($request->isMethod('OPTIONS')) {
            return response()->json([], 200, $headers);
        }

        $this->authorizeAccess($request, $headers);

        return response()->json($monitor->getAllMetrics(), 200, $headers);
    }

    /**
     * Verify API token if configured.
     */
    protected function authorizeAccess(Request $request, array $headers = []): void
    {
        $expectedToken = config('monitoring-server.api_token');

        if (!empty($expectedToken)) {
            $givenToken = $request->query('token')
                ?: $request->header('X-Monitoring-Token')
                ?: $request->bearerToken();

            if (empty($givenToken) || !hash_equals((string) $expectedToken, (string) $givenToken)) {
                if ($request->expectsJson() || $request->isMethod('OPTIONS') || $request->is('api/*')) {
                    abort(response()->json([
                        'error' => 'Unauthorized',
                        'message' => 'Akses ditolak: Token monitoring tidak valid atau belum diberikan.'
                    ], 403, $headers));
                }

                abort(403, 'Akses ditolak: Token monitoring tidak valid atau belum diberikan.');
            }
        }
    }

    /**
     * Get CORS headers for cross-origin / central dashboard support.
     */
    protected function corsHeaders(): array
    {
        if (!config('monitoring-server.cors', true)) {
            return [];
        }

        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Monitoring-Token, Authorization, Accept, X-Requested-With',
            'Access-Control-Max-Age' => '86400',
        ];
    }
}
