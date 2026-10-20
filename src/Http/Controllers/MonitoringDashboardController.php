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
        $this->authorizeAccess($request);

        return response()->json($monitor->getAllMetrics());
    }

    /**
     * Verify API token if configured.
     */
    protected function authorizeAccess(Request $request): void
    {
        $expectedToken = config('monitoring-server.api_token');

        if (!empty($expectedToken)) {
            $givenToken = $request->query('token')
                ?: $request->header('X-Monitoring-Token')
                ?: $request->bearerToken();

            if ($givenToken !== $expectedToken) {
                abort(403, 'Akses ditolak: Token monitoring tidak valid atau belum diberikan.');
            }
        }
    }
}
