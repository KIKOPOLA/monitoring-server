<?php

namespace Kikopola\MonitoringServer\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

class ServerMonitor
{
    /**
     * Get all server metrics at once.
     */
    public function getAllMetrics(): array
    {
        return [
            'system' => $this->getSystemInfo(),
            'cpu' => $this->getCpuMetrics(),
            'memory' => $this->getMemoryMetrics(),
            'disk' => $this->getDiskMetrics(),
            'database' => $this->getDatabaseMetrics(),
            'timestamp' => function_exists('now') ? now()->toIso8601String() : date('c'),
        ];
    }

    /**
     * Get operating system and runtime information.
     */
    public function getSystemInfo(): array
    {
        $osFamily = PHP_OS_FAMILY;
        $uptime = $this->calculateUptime($osFamily);
        $osString = $this->detectOperatingSystem($osFamily);

        return [
            'os' => $osString,
            'os_family' => $osFamily,
            'hostname' => gethostname() ?: 'unknown',
            'php_version' => PHP_VERSION,
            'laravel_version' => function_exists('app') ? app()->version() : 'N/A',
            'server_ip' => (function_exists('request') && request()) ? (request()->server('SERVER_ADDR') ?: gethostbyname(gethostname())) : gethostbyname(gethostname()),
            'uptime' => $uptime,
        ];
    }

    /**
     * Dynamically detect full operating system name from OS hardware/kernel APIs.
     */
    protected function detectOperatingSystem(string $family): string
    {
        if ($family === 'Windows') {
            // Baca langsung dari Windows CIM/WMI
            $caption = @shell_exec('powershell -NoProfile -Command "(Get-CimInstance Win32_OperatingSystem).Caption"');
            if ($caption && trim($caption) !== '') {
                $versionInfo = php_uname('v');
                $buildSuffix = '';
                if (preg_match('/build\s+(\d+)/i', $versionInfo, $m)) {
                    $buildSuffix = " (Build {$m[1]})";
                }
                return trim($caption) . $buildSuffix;
            }

            // Fallback: baca build kernel dari php_uname
            $v = php_uname('v');
            if (preg_match('/build\s+(\d+)/i', $v, $m)) {
                $b = (int) $m[1];
                $edition = $b >= 22000 ? 'Windows 11' : 'Windows 10';
                return "Microsoft {$edition} (Build {$b})";
            }

            return php_uname('s') . ' ' . php_uname('r');
        }

        if ($family === 'Linux') {
            // Baca distribusi resmi dari /etc/os-release (Ubuntu, Debian, CentOS, AlmaLinux, dsb)
            if (@is_readable('/etc/os-release')) {
                $content = @file_get_contents('/etc/os-release');
                if ($content && preg_match('/PRETTY_NAME="([^"]+)"/i', $content, $m)) {
                    return $m[1] . ' (Kernel ' . php_uname('r') . ')';
                }
            }
            return php_uname('s') . ' ' . php_uname('r');
        }

        if ($family === 'Darwin') {
            $ver = @shell_exec('sw_vers -productVersion');
            return 'macOS ' . ($ver ? trim($ver) : php_uname('r'));
        }

        return php_uname('s') . ' ' . php_uname('r');
    }

    /**
     * Get real-time disk space usage.
     */
    public function getDiskMetrics(?string $path = null): array
    {
        $targetPath = $path ?: (function_exists('base_path') ? base_path() : __DIR__);

        $freeBytes = @disk_free_space($targetPath);
        $totalBytes = @disk_total_space($targetPath);

        if ($freeBytes === false || $totalBytes === false || $totalBytes <= 0) {
            return [
                'total' => '0 B',
                'used' => '0 B',
                'free' => '0 B',
                'used_percentage' => 0,
                'status' => 'unknown',
            ];
        }

        $usedBytes = $totalBytes - $freeBytes;
        $usedPercentage = round(($usedBytes / $totalBytes) * 100, 2);

        return [
            'total' => $this->formatBytes($totalBytes),
            'used' => $this->formatBytes($usedBytes),
            'free' => $this->formatBytes($freeBytes),
            'total_bytes' => $totalBytes,
            'used_bytes' => $usedBytes,
            'free_bytes' => $freeBytes,
            'used_percentage' => $usedPercentage,
            'status' => $usedPercentage >= 90 ? 'critical' : ($usedPercentage >= 80 ? 'warning' : 'ok'),
        ];
    }

    /**
     * Get real-time RAM / Memory usage from the OS.
     */
    public function getMemoryMetrics(): array
    {
        $osFamily = PHP_OS_FAMILY;
        $totalBytes = 0;
        $freeBytes = 0;

        if ($osFamily === 'Linux') {
            if (@is_readable('/proc/meminfo')) {
                $meminfo = @file_get_contents('/proc/meminfo');
                if ($meminfo) {
                    preg_match('/MemTotal:\s+(\d+)\s+kB/i', $meminfo, $totalMatch);
                    preg_match('/MemAvailable:\s+(\d+)\s+kB/i', $meminfo, $availMatch);

                    if (!empty($totalMatch[1]) && !empty($availMatch[1])) {
                        $totalBytes = ((int) $totalMatch[1]) * 1024;
                        $freeBytes = ((int) $availMatch[1]) * 1024;
                    }
                }
            }
        } elseif ($osFamily === 'Windows') {
            $cmd = 'powershell -NoProfile -Command "(Get-CimInstance Win32_OperatingSystem) | Select-Object TotalVisibleMemorySize,FreePhysicalMemory | ConvertTo-Json"';
            $output = @shell_exec($cmd);
            if ($output) {
                $data = @json_decode(trim($output), true);
                if (isset($data['TotalVisibleMemorySize'], $data['FreePhysicalMemory'])) {
                    $totalBytes = ((int) $data['TotalVisibleMemorySize']) * 1024;
                    $freeBytes = ((int) $data['FreePhysicalMemory']) * 1024;
                }
            }
        }

        // Fallback to PHP process memory if OS memory retrieval fails
        if ($totalBytes <= 0) {
            $phpUsed = memory_get_usage(true);
            return [
                'total' => 'N/A (PHP limit: ' . ini_get('memory_limit') . ')',
                'used' => $this->formatBytes($phpUsed),
                'free' => 'N/A',
                'used_percentage' => 0,
                'php_usage' => $this->formatBytes($phpUsed),
                'status' => 'ok',
            ];
        }

        $usedBytes = $totalBytes - $freeBytes;
        $usedPercentage = round(($usedBytes / $totalBytes) * 100, 2);

        return [
            'total' => $this->formatBytes($totalBytes),
            'used' => $this->formatBytes($usedBytes),
            'free' => $this->formatBytes($freeBytes),
            'total_bytes' => $totalBytes,
            'used_bytes' => $usedBytes,
            'free_bytes' => $freeBytes,
            'used_percentage' => $usedPercentage,
            'php_usage' => $this->formatBytes(memory_get_usage(true)),
            'status' => $usedPercentage >= 90 ? 'critical' : ($usedPercentage >= 80 ? 'warning' : 'ok'),
        ];
    }

    /**
     * Get real-time CPU load / percentage.
     */
    public function getCpuMetrics(): array
    {
        $osFamily = PHP_OS_FAMILY;
        $loadPercentage = 0.0;
        $loadAvg = [];

        if ($osFamily === 'Linux') {
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                if ($load !== false && count($load) >= 3) {
                    $loadAvg = [
                        '1m' => round($load[0], 2),
                        '5m' => round($load[1], 2),
                        '15m' => round($load[2], 2),
                    ];
                    $loadPercentage = round($load[0] * 10, 2);
                }
            }
        } elseif ($osFamily === 'Windows') {
            $cmd = 'powershell -NoProfile -Command "(Get-CimInstance Win32_Processor | Measure-Object -Property LoadPercentage -Average).Average"';
            $output = @shell_exec($cmd);
            if ($output !== null && is_numeric(trim($output))) {
                $loadPercentage = (float) trim($output);
            }
        }

        return [
            'load_percentage' => $loadPercentage,
            'load_average' => $loadAvg,
            'status' => $loadPercentage >= 85 ? 'critical' : ($loadPercentage >= 70 ? 'warning' : 'ok'),
        ];
    }

    /**
     * Get real-time database connection status and query latency.
     */
    public function getDatabaseMetrics(): array
    {
        $startTime = microtime(true);

        try {
            $connection = DB::connection();
            $pdo = $connection->getPdo();
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'connected' => true,
                'driver' => $connection->getDriverName(),
                'database' => $connection->getDatabaseName(),
                'latency_ms' => $latencyMs,
                'status' => $latencyMs > 500 ? 'warning' : 'ok',
                'message' => 'Connected successfully (' . $latencyMs . ' ms)',
            ];
        } catch (Throwable $e) {
            return [
                'connected' => false,
                'driver' => function_exists('config') ? config('database.default', 'unknown') : 'unknown',
                'database' => 'unknown',
                'latency_ms' => null,
                'status' => 'critical',
                'message' => (function_exists('config') && config('app.debug'))
                    ? $e->getMessage()
                    : 'Gagal terhubung ke database server.',
            ];
        }
    }

    /**
     * Calculate server uptime string.
     */
    protected function calculateUptime(string $osFamily): string
    {
        $seconds = 0;

        if ($osFamily === 'Linux' && @is_readable('/proc/uptime')) {
            $uptimeContent = @file_get_contents('/proc/uptime');
            if ($uptimeContent) {
                $parts = explode(' ', trim($uptimeContent));
                $seconds = (int) ($parts[0] ?? 0);
            }
        } elseif ($osFamily === 'Windows') {
            $cmd = 'powershell -NoProfile -Command "((Get-Date) - (Get-CimInstance Win32_OperatingSystem).LastBootUpTime).TotalSeconds"';
            $output = @shell_exec($cmd);
            if ($output !== null && is_numeric(trim($output))) {
                $seconds = (int) trim($output);
            }
        }

        if ($seconds <= 0) {
            return 'N/A';
        }

        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $parts = [];
        if ($days > 0) $parts[] = "{$days}d";
        if ($hours > 0) $parts[] = "{$hours}h";
        $parts[] = "{$minutes}m";

        return implode(' ', $parts);
    }

    /**
     * Format bytes to human readable format (KB, MB, GB, TB).
     */
    protected function formatBytes(float|int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
