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
     * The process resource for the PHP built-in server.
     */
    protected $serverProcess = null;

    /**
     * The pipes array for the PHP built-in server.
     */
    protected array $serverPipes = [];

    /**
     * Guard flag to prevent duplicate cleanup on exit.
     */
    protected bool $isExiting = false;

    /**
     * Flag indicating whether server request activity is actively streaming.
     */
    protected bool $isStreaming = false;

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

        // Pastikan port bersih dari proses lama sebelum menjalankan server baru
        $this->stopServerOnPort($dedicatedPort);

        // Nyalakan server PHP built-in via proc_open agar log aktivitas dapat ditangkap
        $this->startServerProcess($dedicatedPort);

        // Daftarkan signal & shutdown handler untuk penanganan Ctrl+C
        $this->registerExitHandler($dedicatedPort);

        // 1. Tampilkan Banner ANSI Shadow (hanya SEKALI di awal)
        $this->displayBanner();

        // 2. Tampilkan Tabel Metrik Hardware (hanya SEKALI di awal)
        $this->renderHardwareTable($monitor);

        // 3. Tampilkan Link Live Dashboard & Petunjuk Ctrl+C (hanya SEKALI)
        $dashboardInfo = "  \033[1;36m🌐 Live Dashboard (Port {$dedicatedPort} Aktif):\033[0m\n"
                       . "  \033[1;33m👉 {$dashboardUrl}\033[0m\n\n"
                       . "  \033[90m💡 Tekan \033[1;33mCtrl+C\033[0m\033[90m untuk menghentikan monitoring dan mematikan server.\033[0m\n\n"
                       . "  \033[90m─────────────────────────────────────────────────────────────────────────────\033[0m\n"
                       . "  \033[1;36m📡 Server Request Activity (Port {$dedicatedPort}):\033[0m\n\n";

        $this->outputRaw($dashboardInfo);

        if ($this->option('open')) {
            $this->outputRaw("  \033[1;32m🚀 Membuka {$dashboardUrl} di browser...\033[0m\n\n");
            $this->openInBrowser($dashboardUrl);
        }

        // Mode satu kali jika opsi --once diberikan
        if ($this->option('once')) {
            return self::SUCCESS;
        }

        // 4. Streaming Aktivitas Request Server secara Real-Time (seperti php artisan serve)
        $this->streamServerActivity();

        return self::SUCCESS;
    }

    /**
     * Render the initial hardware telemetry metrics table.
     */
    protected function renderHardwareTable(ServerMonitor $monitor): void
    {
        $infoText = "  \033[1;36m⚡ Metrik Hardware Real-Time (Live Telemetry):\033[0m\n\n";
        $this->outputRaw($infoText);

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
        $this->outputRaw("\n");
    }

    /**
     * Start the PHP built-in development server via proc_open.
     */
    protected function startServerProcess(int $port): void
    {
        $baseDir = function_exists('base_path') ? base_path() : getcwd();
        $entryArg = file_exists($baseDir . '/server.php') ? 'server.php' : '-t public';
        $cmd = "php -S 127.0.0.1:{$port} {$entryArg}";

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'], // php -S outputs request logs to STDERR
        ];

        $this->serverProcess = @proc_open($cmd, $descriptors, $this->serverPipes, $baseDir);

        if (is_resource($this->serverProcess)) {
            if (isset($this->serverPipes[1]) && is_resource($this->serverPipes[1])) {
                stream_set_blocking($this->serverPipes[1], false);
            }
            if (isset($this->serverPipes[2]) && is_resource($this->serverPipes[2])) {
                stream_set_blocking($this->serverPipes[2], false);
            }
        }

        // Wait up to 1.5 seconds for port to become active
        for ($i = 0; $i < 15; $i++) {
            usleep(100000);
            if ($this->isPortActive('127.0.0.1', $port)) {
                break;
            }
        }
    }

    /**
     * Stream real-time PHP server logs to the console (like php artisan serve).
     */
    protected function streamServerActivity(): void
    {
        $this->isStreaming = true;

        while (true) {
            // Check if server process unexpectedly died
            if (is_resource($this->serverProcess)) {
                $status = @proc_get_status($this->serverProcess);
                if (is_array($status) && !$status['running']) {
                    $this->outputRaw("\n  \033[1;31m⚠️ Server PHP terhenti secara tidak terduga.\033[0m\n\n");
                    break;
                }
            }

            // Read lines from STDERR (where php -S writes request logs)
            if (isset($this->serverPipes[2]) && is_resource($this->serverPipes[2])) {
                while (($rawLine = fgets($this->serverPipes[2])) !== false) {
                    $formatted = $this->formatServerLogLine($rawLine);
                    if ($formatted !== null) {
                        $this->outputRaw($formatted . "\n");
                    }
                }
            }

            // Also check STDOUT just in case
            if (isset($this->serverPipes[1]) && is_resource($this->serverPipes[1])) {
                while (($rawLine = fgets($this->serverPipes[1])) !== false) {
                    $formatted = $this->formatServerLogLine($rawLine);
                    if ($formatted !== null) {
                        $this->outputRaw($formatted . "\n");
                    }
                }
            }

            usleep(40000); // 40ms interval, 0% CPU idle
        }
    }

    /**
     * Format a raw PHP development server log line into a clean, modern CLI log (like php artisan serve).
     */
    protected function formatServerLogLine(string $rawLine): ?string
    {
        $rawLine = trim($rawLine);
        if (empty($rawLine)) {
            return null;
        }

        // Ignore internal socket handshake noise
        if (str_contains($rawLine, 'Accepted') || str_contains($rawLine, 'Closing') || str_contains($rawLine, 'Closed without sending')) {
            return null;
        }

        $gray = "\033[90m";
        $white = "\033[1;37m";
        $cyan = "\033[36m";
        $reset = "\033[0m";

        // Initial server start banner
        if (str_contains($rawLine, 'Development Server') && str_contains($rawLine, 'started')) {
            $time = date('H:i:s');
            return "  {$gray}[{$time}]{$reset} \033[1;32m[START]\033[0m  PHP Development Server active on http://127.0.0.1:8989";
        }

        // Match HTTP request line: [Tue Oct 20 10:25:36 2026] 127.0.0.1:62741 [200]: POST /_boost/browser-logs
        if (preg_match('/\[([^\]]+)\]\s+([^\s]+)\s+\[(\d+)\]:\s+(\w+)\s+(.+)/', $rawLine, $m)) {
            $timestamp = date('H:i:s');
            $code = (int) $m[3];
            $method = str_pad($m[4], 6);
            $uri = $m[5];

            $codeText = match ($code) {
                200 => '200 OK',
                201 => '201 CREATED',
                204 => '204 NO CONTENT',
                301, 302 => "{$code} REDIRECT",
                304 => '304 NOT MODIFIED',
                400 => '400 BAD REQUEST',
                401 => '401 UNAUTHORIZED',
                403 => '403 FORBIDDEN',
                404 => '404 NOT FOUND',
                405 => '405 METHOD NOT ALLOWED',
                422 => '422 UNPROCESSABLE',
                500 => '500 ERROR',
                default => (string) $code,
            };

            $codeBadge = match (true) {
                $code >= 200 && $code < 300 => "\033[1;32m[{$codeText}]\033[0m",
                $code >= 300 && $code < 400 => "\033[1;33m[{$codeText}]\033[0m",
                $code >= 400 && $code < 500 => "\033[1;33m[{$codeText}]\033[0m",
                default => "\033[1;31m[{$codeText}]\033[0m",
            };

            return "  {$gray}[{$timestamp}]{$reset} {$codeBadge}  {$white}{$method}{$reset} {$cyan}{$uri}{$reset}";
        }

        // Fallback for warnings, errors, or custom notices
        $time = date('H:i:s');
        return "  {$gray}[{$time}]{$reset} {$rawLine}";
    }

    /**
     * Backward-compatible alias for renderIteration.
     */
    protected function renderDashboardIteration(ServerMonitor $monitor, int $dedicatedPort, string $dashboardUrl, bool $isLiveLoop): void
    {
        $this->displayBanner();
        $this->renderHardwareTable($monitor);
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
            $this->cleanupAndExit($port);
        });
    }

    /**
     * Clean up background processes and exit cleanly upon Ctrl+C or termination.
     */
    protected function cleanupAndExit(int $port): void
    {
        if ($this->isExiting) {
            return;
        }
        $this->isExiting = true;

        if ($this->isStreaming) {
            $msg = "\n\n  \033[1;33m🛑 Monitoring dihentikan. Server port {$port} telah dimatikan. Sampai jumpa!\033[0m\n\n";
            $this->outputRaw($msg);
        }

        if (is_array($this->serverPipes)) {
            foreach ($this->serverPipes as $pipe) {
                if (is_resource($pipe)) {
                    @fclose($pipe);
                }
            }
        }

        if (is_resource($this->serverProcess)) {
            @proc_terminate($this->serverProcess);
            @proc_close($this->serverProcess);
            $this->serverProcess = null;
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
     * Backward-compatible alias for starting server in background.
     */
    protected function ensureServerRunning(int $port): void
    {
        if (!$this->isPortActive('127.0.0.1', $port)) {
            $this->startServerProcess($port);
        }
    }

    /**
     * Write raw text to stdout without mangling ANSI escape sequences.
     */
    protected function outputRaw(string $text): void
    {
        if (defined('STDOUT') && is_resource(STDOUT)) {
            fwrite(STDOUT, $text);
        } elseif ($this->output) {
            $this->output->writeln($text, \Symfony\Component\Console\Output\OutputInterface::OUTPUT_RAW);
        } else {
            echo $text;
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

        $this->outputRaw($bannerText);
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

        $this->outputRaw($tableText);
    }

    /**
     * Strip ANSI escape codes from string for accurate length calculation.
     */
    protected function stripAnsi(string $str): string
    {
        return preg_replace('/\033\[[0-9;]*m/', '', $str);
    }
}
