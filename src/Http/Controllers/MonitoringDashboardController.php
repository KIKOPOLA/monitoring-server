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
        // 1. Standalone password protection gate
        if ($this->isPasswordProtected() && !$this->isAuthenticated($request)) {
            $dashboardPath = config('monitoring-server.dashboard_path', 'monitoring');
            return view('monitoring-server::login', [
                'dashboardPath' => $dashboardPath,
                'hasUsername' => !empty(config('monitoring-server.username')),
            ]);
        }

        // 2. Token-based gate (if configured)
        $this->authorizeAccess($request);

        $metrics = $monitor->getAllMetrics();
        $apiPrefix = config('monitoring-server.api_prefix', 'api/monitoring');
        $apiEndpoint = url(trim($apiPrefix, '/') . '/metrics');

        return view('monitoring-server::dashboard', [
            'metrics' => $metrics,
            'apiEndpoint' => $apiEndpoint,
            'token' => $request->query('token') ?: $request->header('X-Monitoring-Token'),
            'isPasswordProtected' => $this->isPasswordProtected(),
        ]);
    }

    /**
     * Handle standalone login submission.
     */
    public function login(Request $request)
    {
        $expectedPassword = config('monitoring-server.password');
        $expectedUsername = config('monitoring-server.username');

        $inputPassword = $request->input('password');
        $inputUsername = $request->input('username');

        $isValid = true;

        if (!empty($expectedUsername)) {
            if (!hash_equals((string) $expectedUsername, (string) $inputUsername)) {
                $isValid = false;
            }
        }

        if (!empty($expectedPassword)) {
            if (!hash_equals((string) $expectedPassword, (string) $inputPassword)) {
                $isValid = false;
            }
        }

        if ($isValid) {
            session(['monitoring_server_auth' => true]);
            $dashboardPath = config('monitoring-server.dashboard_path', 'monitoring');
            return redirect('/' . trim($dashboardPath, '/'));
        }

        return back()->with('error', 'Password atau Username akses monitoring salah!')->withInput();
    }

    /**
     * Handle logout action.
     */
    public function logout(Request $request)
    {
        session()->forget('monitoring_server_auth');
        $dashboardPath = config('monitoring-server.dashboard_path', 'monitoring');
        return redirect('/' . trim($dashboardPath, '/'));
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

        // Standalone password check
        if ($this->isPasswordProtected() && !$this->isAuthenticated($request)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Akses ditolak: Password monitoring diperlukan.'
            ], 401, $headers);
        }

        $this->authorizeAccess($request, $headers);

        return response()->json($monitor->getAllMetrics(), 200, $headers);
    }

    /**
     * Determine if standalone password protection is active.
     */
    protected function isPasswordProtected(): bool
    {
        return !empty(config('monitoring-server.password'));
    }

    /**
     * Check if current session or request is authenticated.
     */
    protected function isAuthenticated(Request $request): bool
    {
        // 1. Session check
        if ($request->hasSession() && $request->session()->get('monitoring_server_auth') === true) {
            return true;
        }

        // 2. Direct password/token bypass via query or header (convenient for API and automated hubs)
        $expectedPassword = config('monitoring-server.password');
        $given = $request->query('password')
            ?: $request->query('token')
            ?: $request->header('X-Monitoring-Token')
            ?: $request->bearerToken();

        if (!empty($given) && !empty($expectedPassword) && hash_equals((string) $expectedPassword, (string) $given)) {
            if ($request->hasSession()) {
                $request->session()->put('monitoring_server_auth', true);
            }
            return true;
        }

        return false;
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
