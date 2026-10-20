<?php

namespace Kikopola\MonitoringServer\Commands;

use Illuminate\Console\Command;

class ServeServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'monitoring:serve
                            {--port=8989 : Port yang digunakan untuk server dashboard}
                            {--host=127.0.0.1 : Host server}
                            {--no-browser : Jangan buka browser otomatis}';

    /**
     * The console command description.
     */
    protected $description = 'Menjalankan server monitoring langsung dan membuka dashboard di browser (by kikopola)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $port = (int) $this->option('port');
        $host = (string) $this->option('host');
        $path = config('monitoring-server.dashboard_path', 'monitoring');
        $url = "http://{$host}:{$port}/" . ltrim($path, '/');

        $this->newLine();
        $this->line('<fg=bright-white;options=bold>  =======================================================</>');
        $this->line('<fg=bright-white;options=bold>   ⚡ </><fg=bright-white;options=bold>MONITORING</> <fg=bright-red;options=bold>SERVER DASHBOARD</> <fg=bright-yellow;options=bold>• by kikopola</>');
        $this->line('<fg=bright-white;options=bold>  =======================================================</>');
        $this->newLine();

        $this->line("  <fg=bright-cyan;options=bold>🌐 Live Dashboard URL :</> <fg=yellow;options=bold>{$url}</>");
        $this->line('  <fg=gray>Tekan Ctrl+C untuk menghentikan server monitoring kapan saja.</>');
        $this->newLine();

        if (! $this->option('no-browser')) {
            $this->info("🚀 Membuka {$url} di web browser...");
            $this->openInBrowser($url);
        }

        $php = PHP_BINARY;
        $cmd = sprintf('"%s" artisan serve --port=%d --host=%s', $php, $port, escapeshellarg($host));

        passthru($cmd, $status);

        return $status === 0 ? self::SUCCESS : self::FAILURE;
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
}
