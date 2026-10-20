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
        // Paksa aktifkan warna ANSI agar selalu tampil berwarna di Git Bash, MinTTY, dan Windows
        if ($this->output) {
            $this->output->setDecorated(true);
            $this->output->getFormatter()->setDecorated(true);
        }

        $this->displayBanner();

        $infoText = "  \033[1;36m⚡ Mengambil data metrik hardware secara real-time...\033[0m\n\n";
        if (defined('STDOUT') && is_resource(STDOUT)) {
            fwrite(STDOUT, $infoText);
        } else {
            $this->info('Mengambil data metrik hardware secara real-time...');
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

        $dedicatedPort = (int) config('monitoring-server.port', 8989);
        $path = config('monitoring-server.dashboard_path', 'monitoring');
        $dashboardUrl = "http://localhost:{$dedicatedPort}/" . ltrim($path, '/');

        // Otomatis nyalakan server port 8989 di background secara permanen
        $this->ensureServerRunning($dedicatedPort);

        $portText = "  \033[1;36m🌐 Live Dashboard (Port {$dedicatedPort} Aktif):\033[0m\n"
                  . "  \033[1;33m👉 {$dashboardUrl}\033[0m\n\n";

        if (defined('STDOUT') && is_resource(STDOUT)) {
            fwrite(STDOUT, $portText);
        } else {
            $this->line("  <fg=cyan;options=bold>🌐 Live Dashboard (Port {$dedicatedPort} Aktif):</>");
            $this->line("  <fg=yellow;options=bold>👉 {$dashboardUrl}</>");
            $this->newLine();
        }

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
