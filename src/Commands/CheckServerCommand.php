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
                            {--open : Buka live dashboard langsung di web browser}
                            {--once : Jalankan pengecekan satu kali saja (tanpa live loop)}';

    /**
     * The console command description.
     */
    protected $description = 'Check real-time server health and resource usage (by kikopola)';

    /**
     * Execute the console command.
     */
    public function handle(ServerMonitor $monitor): int
    {
        // Paksa aktifkan warna ANSI agar selalu tampil berwarna di Git Bash, MinTTY, dan Windows
        if ($this->output) {
            $this->output->setDecorated(true);
            $this->output->getFormatter()->setDecorated(true);
        }

        $dedicatedPort = (int) config('monitoring-server.port', 8989);
        $path = config('monitoring-server.dashboard_path', 'monitoring');
        $dashboardUrl = "http://localhost:{$dedicatedPort}/" . ltrim($path, '/');

        // Otomatis nyalakan server port 8989 di background
        $this->ensureServerRunning($dedicatedPort);

        if ($this->option('open')) {
            $this->info("🚀 Membuka {$dashboardUrl} di browser...");
            $this->openInBrowser($dashboardUrl);
        }

        // Mode satu kali (non-looping) jika opsi --once diberikan
        if ($this->option('once')) {
            $this->renderDashboardIteration($monitor, $dedicatedPort, $dashboardUrl, false);
            return self::SUCCESS;
        }

        // Mode live interaktif (berhenti dan matikan server saat pengguna menekan Ctrl+C)
        $this->registerExitHandler($dedicatedPort);

        $first = true;
        while (true) {
            if (!$first) {
                if (defined('STDOUT') && is_resource(STDOUT)) {
                    fwrite(STDOUT, "\033[H\033[2J");
                } else {
                    echo "\033[H\033[2J";
                }
            }
            $first = false;

            $this->renderDashboardIteration($monitor, $dedicatedPort, $dashboardUrl, true);

            // Jeda 2.5 detik (dibagi irisan kecil 0.25s agar Ctrl+C responsif seketika)
            for ($i = 0; $i < 10; $i++) {
                usleep(250000);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Render one complete frame of the dashboard telemetry in CLI.
     */
    protected function renderDashboardIteration(ServerMonitor $monitor, int $dedicatedPort, string $dashboardUrl, bool $isLiveLoop): void
    {
        $this->displayBanner();

        $infoText = "  \033[1;36m⚡ Metrik Hardware Real-Time (Live Telemetry):\033[0m\n\n";
        if (defined('STDOUT') && is_resource(STDOUT)) {
            fwrite(STDOUT, $infoText);
        } else {
            $this->info('Metrik Hardware Real-Time (Live Telemetry):');
            $this->newLine();
        }

        $system = $monitor->getSystemInfo();
        $cpu = $monitor->getCpuMetrics();
        $memory = $monitor->getMemoryMetrics();
        $disk = $monitor->getDiskMetrics();
        $db = $monitor->getDatabaseMetrics();

        $rows = [
            [
                'Sistem Operasi',
                "{$system['os']} ({$system['hostname']})",
                "\033[1;32m[OK]\033[0m"
            ],
            [
                'PHP Runtime',
                "v{$system['php_version']} • Laravel {$system['laravel_version']}",
                "\033[1;32m[OK]\033[0m"
            ],
            [
                'Server Uptime',
                $system['uptime'],
                "\033[1;32m[OK]\033[0m"
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

        $this->renderMetricsTable(['Metrik Hardware', 'Kondisi Real-Time', 'Status'], $rows);
        $this->newLine();

        $portText = "  \033[1;36m🌐 Live Dashboard (Port {$dedicatedPort} Aktif):\033[0m\n"
                  . "  \033[1;33m👉 {$dashboardUrl}\033[0m\n";

        if ($isLiveLoop) {
            $portText .= "\n  \033[90m💡 Tekan \033[1;33mCtrl+C\033[0m\033[90m untuk menghentikan monitoring dan mematikan server.\033[0m\n\n";
        } else {
            $portText .= "\n";
        }

        if (defined('STDOUT') && is_resource(STDOUT)) {
            fwrite(STDOUT, $portText);
        } else {
            $this->line("  <fg=cyan;options=bold>🌐 Live Dashboard (Port {$dedicatedPort} Aktif):</>");
            $this->line("  <fg=yellow;options=bold>👉 {$dashboardUrl}</>");
            if ($isLiveLoop) {
                $this->line("  <fg=gray>💡 Tekan Ctrl+C untuk menghentikan monitoring dan mematikan server.</>");
            }
            $this->newLine();
        }
    }

    /**
     * Register signal and shutdown handlers for graceful Ctrl+C termination.
     */
    protected function registerExitHandler(int $port): void
    {
        if (function_exists('sapi_windows_set_ctrl_handler')) {
            sapi_windows_set_ctrl_handler(function (int $event) use ($port) {
                if ($event === PHP_WINDOWS_EVENT_CTRL_C || $event === PHP_WINDOWS_EVENT_CTRL_BREAK) {
                    $this->cleanupAndExit($port);
                }
            });
        }

        if (function_exists('pcntl_async_signals') && function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, function () use ($port) {
                $this->cleanupAndExit($port);
            });
            pcntl_signal(SIGTERM, function () use ($port) {
                $this->cleanupAndExit($port);
            });
        }

        register_shutdown_function(function () use ($port) {
            $this->stopServerOnPort($port);
        });
    }

    /**
     * Clean up background processes and exit cleanly upon Ctrl+C or termination.
     */
    protected function cleanupAndExit(int $port): void
    {
        $msg = "\n\n  \033[1;33m🛑 Monitoring dihentikan. Server port {$port} telah dimatikan. Sampai jumpa!\033[0m\n\n";
        if (defined('STDOUT') && is_resource(STDOUT)) {
            fwrite(STDOUT, $msg);
        } else {
            echo $msg;
        }
        $this->stopServerOnPort($port);
        exit(0);
    }

    /**
     * Stop the server process listening on the specified port.
     */
    public function stopServerOnPort(int $port): bool
    {
        $pid = $this->getProcessIdOnPort($port);
        if ($pid && $pid > 0) {
            if (PHP_OS_FAMILY === 'Windows') {
                @exec("taskkill /F /PID {$pid} > nul 2>&1");
            } else {
                @exec("kill -9 {$pid} > /dev/null 2>&1");
            }
            return true;
        }
        return false;
    }

    /**
     * Get the PID of the process listening on a port.
     */
    protected function getProcessIdOnPort(int $port): ?int
    {
        $output = [];
        if (PHP_OS_FAMILY === 'Windows') {
            @exec("netstat -ano -p tcp | findstr :{$port}", $output);
            foreach ($output as $line) {
                $line = trim($line);
                if (preg_match('/LISTENING\s+(\d+)$/i', $line, $matches)) {
                    return (int) $matches[1];
                }
            }
        } else {
            @exec("lsof -t -i:{$port} -sTCP:LISTEN 2>/dev/null", $output);
            if (!empty($output[0])) {
                return (int) trim($output[0]);
            }
        }
        return null;
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
     * Display the console banner.
     */
    protected function displayBanner(): void
    {
        $white = "\033[1;37m";
        $red = "\033[1;31m";
        $yellow = "\033[1;33m";
        $gray = "\033[90m";
        $reset = "\033[0m";

        // ANSI Shadow font (matching the user's requested 3D block isometric font style)
        $mon = [
            "███╗   ███╗ ██████╗ ███╗   ██╗██╗████████╗ ██████╗ ██████╗ ██╗███╗   ██╗ ██████╗ ",
            "████╗ ████║██╔═══██╗████╗  ██║██║╚══██╔══╝██╔═══██╗██╔══██╗██║████╗  ██║██╔════╝ ",
            "██╔████╔██║██║   ██║██╔██╗ ██║██║   ██║   ██║   ██║██████╔╝██║██╔██╗ ██║██║  ███╗",
            "██║╚██╔╝██║██║   ██║██║╚██╗██║██║   ██║   ██║   ██║██╔══██╗██║██║╚██╗██║██║   ██║",
            "██║ ╚═╝ ██║╚██████╔╝██║ ╚████║██║   ██║   ╚██████╔╝██║  ██║██║██║ ╚████║╚██████╔╝",
            "╚═╝     ╚═╝ ╚═════╝ ╚═╝  ╚═══╝╚═╝   ╚═╝    ╚═════╝ ╚═╝  ╚═╝╚═╝╚═╝  ╚═══╝ ╚═════╝ ",
        ];

        $srv = [
            "███████╗███████╗██████╗ ██╗   ██╗███████╗██████╗ ",
            "██╔════╝██╔════╝██╔══██╗██║   ██║██╔════╝██╔══██╗",
            "███████╗█████╗  ██████╔╝██║   ██║█████╗  ██╔══██╗",
            "╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██╔══╝  ██╔══██╗",
            "███████║███████╗██║  ██║ ╚████╔╝ ███████╗██║  ██║",
            "╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚══════╝╚═╝  ╚═╝",
        ];

        $termWidth = 120;
        try {
            $termWidth = (new \Symfony\Component\Console\Terminal())->getWidth();
        } catch (\Throwable $e) {
            $termWidth = 120;
        }

        $lines = [];
        if ($termWidth >= 135) {
            // Side-by-side if terminal width is wide enough
            for ($i = 0; $i < 6; $i++) {
                $lines[] = "  {$white}{$mon[$i]}{$reset}   {$red}{$srv[$i]}{$reset}";
            }
        } else {
            // Stacked if terminal is standard/narrower (<= 120 cols) so lines never wrap/break
            foreach ($mon as $line) {
                $lines[] = "  {$white}{$line}{$reset}";
            }
            $lines[] = "";
            foreach ($srv as $line) {
                $lines[] = "                 {$red}{$line}{$reset}";
            }
        }

        $bannerText = "\n" . implode("\n", $lines) . "\n"
            . "               {$yellow}⚡{$reset} {$white}MONITORING{$reset} {$red}SERVER{$reset} {$gray}•{$reset} {$yellow}by kikopola v1.0 ⚡{$reset}\n"
            . "  {$gray}─────────────────────────────────────────────────────────────────────────────{$reset}\n\n";

        if (defined('STDOUT') && is_resource(STDOUT)) {
            fwrite(STDOUT, $bannerText);
        } elseif ($this->output) {
            $this->output->writeln($bannerText, \Symfony\Component\Console\Output\OutputInterface::OUTPUT_RAW);
        } else {
            echo $bannerText;
        }
    }

    /**
     * Format status badge with console colors.
     */
    protected function formatStatusTag(string $status): string
    {
        return match ($status) {
            'ok' => "\033[1;32m[OK]\033[0m",
            'warning' => "\033[1;33m[WARNING]\033[0m",
            'critical' => "\033[1;31m[CRITICAL]\033[0m",
            default => "\033[90m[UNKNOWN]\033[0m",
        };
    }

    /**
     * Render the metrics table with mathematical alignment and rich box borders.
     */
    protected function renderMetricsTable(array $headers, array $rows): void
    {
        $colCount = count($headers);
        $widths = array_fill(0, $colCount, 0);

        for ($c = 0; $c < $colCount; $c++) {
            $widths[$c] = max($widths[$c], mb_strlen($this->stripAnsi($headers[$c])));
        }
        foreach ($rows as $row) {
            for ($c = 0; $c < $colCount; $c++) {
                $val = $row[$c] ?? '';
                $widths[$c] = max($widths[$c], mb_strlen($this->stripAnsi($val)));
            }
        }

        $gray = "\033[90m";
        $white = "\033[1;37m";
        $reset = "\033[0m";

        $topParts = [];
        $midParts = [];
        $botParts = [];
        foreach ($widths as $w) {
            $topParts[] = str_repeat('─', $w + 2);
            $midParts[] = str_repeat('─', $w + 2);
            $botParts[] = str_repeat('─', $w + 2);
        }
        $top = "  {$gray}┌" . implode('┬', $topParts) . "┐{$reset}";
        $mid = "  {$gray}├" . implode('┼', $midParts) . "┤{$reset}";
        $bot = "  {$gray}└" . implode('┴', $botParts) . "┘{$reset}";

        $lines = [$top];

        // Header row
        $headerCells = [];
        for ($c = 0; $c < $colCount; $c++) {
            $hText = $headers[$c];
            $pad = $widths[$c] - mb_strlen($this->stripAnsi($hText));
            $headerCells[] = ' ' . $white . $hText . $reset . str_repeat(' ', $pad) . ' ';
        }
        $lines[] = "  {$gray}│{$reset}" . implode("{$gray}│{$reset}", $headerCells) . "{$gray}│{$reset}";
        $lines[] = $mid;

        // Data rows
        foreach ($rows as $row) {
            $cells = [];
            for ($c = 0; $c < $colCount; $c++) {
                $val = (string) ($row[$c] ?? '');
                $visLen = mb_strlen($this->stripAnsi($val));
                $pad = max(0, $widths[$c] - $visLen);
                $cells[] = ' ' . $val . str_repeat(' ', $pad) . ' ';
            }
            $lines[] = "  {$gray}│{$reset}" . implode("{$gray}│{$reset}", $cells) . "{$gray}│{$reset}";
        }

        $lines[] = $bot;

        $tableText = implode("\n", $lines) . "\n";

        if (defined('STDOUT') && is_resource(STDOUT)) {
            fwrite(STDOUT, $tableText);
        } elseif ($this->output) {
            $this->output->writeln($tableText, \Symfony\Component\Console\Output\OutputInterface::OUTPUT_RAW);
        } else {
            echo $tableText;
        }
    }

    /**
     * Strip ANSI escape codes from string for accurate length calculation.
     */
    protected function stripAnsi(string $str): string
    {
        return preg_replace('/\033\[[0-9;]*m/', '', $str);
    }
}
