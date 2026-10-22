<?php

namespace Kikopola\MonitoringServer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Kikopola\MonitoringServer\Services\ServerMonitor;
use Throwable;

class HeartbeatCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'monitoring:heartbeat
                            {--url= : URL endpoint Hub Pusat (contoh: http://domain-hub.com/api/monitor/heartbeat)}
                            {--token= : Token autentikasi untuk Hub Pusat}';

    /**
     * The console command description.
     */
    protected $description = 'Kirim laporan status dan metrics server (Heartbeat) ke Dashboard Pusat / Hub (by kikopola)';

    /**
     * Execute the console command.
     */
    public function handle(ServerMonitor $monitor): int
    {
        if ($this->output) {
            $this->output->setDecorated(true);
            $this->output->getFormatter()->setDecorated(true);
        }

        $hubUrl = $this->option('url') ?: config('monitoring-server.hub_url');
        $hubToken = $this->option('token') ?: config('monitoring-server.hub_token');

        if (empty($hubUrl)) {
            $this->newLine();
            $this->line('  <fg=bright-red;options=bold>✖ Gagal:</> <fg=yellow>URL Hub Pusat belum dikonfigurasi.</>');
            $this->line('  Tambahkan baris berikut di file <fg=bright-white;options=bold>.env</> Anda:');
            $this->line('  <fg=cyan>MONITORING_SERVER_HUB_URL=http://localhost:8000/api/monitor/heartbeat</>');
            $this->newLine();
            return self::FAILURE;
        }

        $appName = config('monitoring-server.app_name', config('app.name', 'Laravel'));
        $appUrl = config('monitoring-server.app_url', config('app.url', 'http://localhost'));

        $this->line("  <fg=cyan;options=bold>⚡ Mengirim Heartbeat</> ke <fg=bright-white;options=bold>{$hubUrl}</> ...");

        try {
            $metrics = $monitor->getAllMetrics();

            $payload = [
                'app_name' => $appName,
                'app_url' => $appUrl,
                'status' => 'UP',
                'timestamp' => function_exists('now') ? now()->toIso8601String() : date('c'),
                'metrics' => $metrics,
            ];

            $httpClient = Http::timeout(8)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'kikopola-monitoring-server/1.0',
                ]);

            if (!empty($hubToken)) {
                $httpClient = $httpClient->withToken($hubToken);
            }

            $startTime = microtime(true);
            $response = $httpClient->post($hubUrl, $payload);
            $elapsedMs = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->successful()) {
                $this->line("  <fg=bright-green;options=bold>✔ Sukses!</> Laporan heartbeat berhasil terkirim dalam <fg=yellow>{$elapsedMs} ms</> (Status: {$response->status()}).");
                $this->line("  <fg=gray>CPU: {$metrics['cpu']['load_percentage']}% • RAM: {$metrics['memory']['used_percentage']}% • Status: UP</>");
                return self::SUCCESS;
            }

            $this->line("  <fg=bright-red;options=bold>✖ Respon Gagal:</> Hub mengembalikan HTTP <fg=yellow>{$response->status()}</>.");
            $this->line("  <fg=gray>Respon: " . substr($response->body(), 0, 150) . "</>");
            return self::FAILURE;
        } catch (Throwable $e) {
            $this->line("  <fg=bright-red;options=bold>✖ Error Koneksi:</> {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
