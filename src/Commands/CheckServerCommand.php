<?php

namespace Kikopola\MonitoringServer\Commands;

use Illuminate\Console\Command;
use Kikopola\MonitoringServer\Services\ServerMonitor;

class CheckServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'monitoring:check
                            {--open : Buka live dashboard langsung di web browser}';

    /**
     * The console command description.
     */
    protected $description = 'Check real-time server health and resource usage (by kikopola)';

    /**
     * Execute the console command.
     */
    public function handle(ServerMonitor $monitor): int
    {
        $this->displayBanner();

        $this->info('Mengambil data metrik hardware secara real-time...');
        $this->newLine();

        $system = $monitor->getSystemInfo();
        $cpu = $monitor->getCpuMetrics();
        $memory = $monitor->getMemoryMetrics();
        $disk = $monitor->getDiskMetrics();
        $db = $monitor->getDatabaseMetrics();

        $rows = [
            [
                'Sistem Operasi',
                "{$system['os']} ({$system['hostname']})",
                '<info>[OK]</info>'
            ],
            [
                'PHP Runtime',
                "v{$system['php_version']} | Laravel {$system['laravel_version']}",
                '<info>[OK]</info>'
            ],
            [
                'Server Uptime',
                $system['uptime'],
                '<info>[OK]</info>'
            ],
            [
                'Penggunaan CPU',
                "{$cpu['load_percentage']}% load",
                $this->formatStatusTag($cpu['status'])
            ],
            [
                'Penggunaan RAM',
                "{$memory['used']} / {$memory['total']} ({$memory['used_percentage']}%)",
                $this->formatStatusTag($memory['status'])
            ],
            [
                'Ruang Harddisk',
                "{$disk['free']} bebas dari {$disk['total']} ({$disk['used_percentage']}% terpakai)",
                $this->formatStatusTag($disk['status'])
            ],
            [
                'Koneksi Database',
                $db['connected']
                    ? "Terhubung ({$db['driver']}) - Latensi {$db['latency_ms']} ms"
                    : "Gagal terhubung ({$db['message']})",
                $this->formatStatusTag($db['status'])
            ],
        ];

        $this->table(['Metrik Hardware', 'Kondisi Real-Time', 'Status'], $rows);
        $this->newLine();

        $dedicatedPort = (int) config('monitoring-server.port', 8989);
        $path = config('monitoring-server.dashboard_path', 'monitoring');
        $dashboardUrl = "http://localhost:{$dedicatedPort}/" . ltrim($path, '/');

        // Otomatis nyalakan server port 8989 di background secara permanen
        $this->ensureServerRunning($dedicatedPort);

        $this->line("  <fg=cyan;options=bold>🌐 Live Dashboard (Port {$dedicatedPort} Aktif):</>");
        $this->line("  <fg=yellow;options=bold>👉 {$dashboardUrl}</>");
        $this->newLine();

        if ($this->option('open')) {
            $this->info("🚀 Membuka {$dashboardUrl} di browser...");
            $this->openInBrowser($dashboardUrl);
        }

        return self::SUCCESS;
    }

    /**
     * Ensure local server is running permanently in background on dedicated port.
     */
    protected function ensureServerRunning(int $port): void
    {
        if ($this->isPortActive('127.0.0.1', $port)) {
            return;
        }

        $baseDir = function_exists('base_path') ? base_path() : getcwd();
        $osFamily = PHP_OS_FAMILY;

        // Deteksi entry point (Laravel 10 pakai server.php, Laravel 11-13 pakai -t public)
        $entryArg = file_exists($baseDir . '/server.php') ? 'server.php' : '-t public';
        $commandLine = "php -S 127.0.0.1:{$port} {$entryArg}";

        if ($osFamily === 'Windows') {
            $escapedDir = addslashes($baseDir);
            $psCmd = "powershell -NoProfile -Command \"Invoke-CimMethod -ClassName Win32_Process -MethodName Create -Arguments @{CommandLine='{$commandLine}'; CurrentDirectory='{$escapedDir}'}\"";
            @exec($psCmd);
        } else {
            @exec("cd " . escapeshellarg($baseDir) . " && nohup {$commandLine} > /dev/null 2>&1 &");
        }

        // Wait up to 1.5 seconds for port to become active
        for ($i = 0; $i < 15; $i++) {
            usleep(100000); // 0.1s
            if ($this->isPortActive('127.0.0.1', $port)) {
                break;
            }
        }
    }

    /**
     * Check if a specific port is actively listening.
     */
    protected function isPortActive(string $host, int $port): bool
    {
        $conn = @fsockopen($host, $port, $errno, $errstr, 0.2);
        if (is_resource($conn)) {
            fclose($conn);
            return true;
        }
        return false;
    }

    /**
     * Open URL in the default web browser.
     */
    protected function openInBrowser(string $url): void
    {
        $osFamily = PHP_OS_FAMILY;

        if ($osFamily === 'Windows') {
            @pclose(@popen("start \"\" \"{$url}\"", 'r'));
        } elseif ($osFamily === 'Darwin') {
            @exec("open \"{$url}\"");
        } else {
            @exec("xdg-open \"{$url}\" > /dev/null 2>&1 &");
        }
    }

    /**
     * Display the ASCII Art banner.
     */
    protected function displayBanner(): void
    {
        $this->newLine();
        $banner = <<<'ASCII'
<fg=white;options=bold>  █▀▄▀█ █▀█ █▄ █ █ ▀█▀ █▀█ █▀█ █ █▄ █ █▀▀ </><fg=red;options=bold> █▀▀ █▀▀ █▀█ █ █ █▀▀ █▀█</>
<fg=white;options=bold>  █ ▀ █ █▄█ █ ▀█ █  █  █▄█ █▀▄ █ █ ▀█ █▄█ </><fg=red;options=bold> ▄██ █▄▄ █▀▄  █  ██▄ █▀▄</>

           ⚡ <fg=white;options=bold>MONITORING</> <fg=red;options=bold>SERVER</> <fg=gray>•</> <fg=yellow;options=bold>by kikopola v1.0</> ⚡
ASCII;

        $this->line($banner);
        $this->newLine();
    }

    /**
     * Format status badge with console colors.
     */
    protected function formatStatusTag(string $status): string
    {
        return match ($status) {
            'ok' => '<fg=green;options=bold>[OK]</>',
            'warning' => '<fg=yellow;options=bold>[WARNING]</>',
            'critical' => '<fg=red;options=bold>[CRITICAL]</>',
            default => '<fg=gray>[UNKNOWN]</>',
        };
    }
}
