<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Monitor &bull; {{ $metrics['system']['hostname'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #09090b;
            --bg-panel: #111113;
            --bg-panel-elevated: #18181b;
            --bg-muted: #27272a;
            --border-dim: #27272a;
            --border-bright: #3f3f46;
            --text-primary: #fafafa;
            --text-secondary: #a1a1aa;
            --text-muted: #71717a;
            --accent-green: #10b981;
            --accent-green-bg: rgba(16, 185, 129, 0.1);
            --accent-amber: #f59e0b;
            --accent-amber-bg: rgba(245, 158, 11, 0.1);
            --accent-rose: #f43f5e;
            --accent-rose-bg: rgba(244, 63, 94, 0.1);
            --accent-cyan: #06b6d4;
            --font-sans: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-base);
            color: var(--text-secondary);
            font-family: var(--font-sans);
            font-size: 0.875rem;
            line-height: 1.5;
            min-height: 100vh;
            padding: 1.5rem;
            -webkit-font-smoothing: antialiased;
        }

        .layout {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        /* Top Navigation Bar */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.875rem 1.25rem;
            background-color: var(--bg-panel);
            border: 1px solid var(--border-dim);
            border-radius: 6px;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .brand-indicator {
            width: 8px;
            height: 8px;
            background-color: var(--accent-green);
            border-radius: 2px;
        }

        .brand-title {
            font-family: var(--font-mono);
            font-weight: 700;
            font-size: 0.9375rem;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }

        .brand-title .pkg-author {
            color: var(--text-muted);
            font-weight: 500;
        }

        .brand-tag {
            font-family: var(--font-mono);
            font-size: 0.6875rem;
            padding: 0.15rem 0.4rem;
            background-color: var(--bg-muted);
            color: var(--text-secondary);
            border-radius: 3px;
            font-weight: 500;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 0.625rem;
        }

        .badge-live {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--text-secondary);
            background-color: var(--bg-panel-elevated);
            border: 1px solid var(--border-dim);
            padding: 0.35rem 0.65rem;
            border-radius: 4px;
        }

        .badge-live .live-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: var(--accent-green);
        }

        .btn-action {
            background-color: var(--bg-panel-elevated);
            color: var(--text-primary);
            border: 1px solid var(--border-dim);
            padding: 0.35rem 0.75rem;
            border-radius: 4px;
            font-family: var(--font-mono);
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.1s ease-in-out;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .btn-action:hover {
            background-color: var(--bg-muted);
            border-color: var(--border-bright);
        }

        .btn-action:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* System Telemetry Specs Strip */
        .specs-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 1px;
            background-color: var(--border-dim);
            border: 1px solid var(--border-dim);
            border-radius: 6px;
            overflow: hidden;
        }

        .spec-cell {
            background-color: var(--bg-panel);
            padding: 0.875rem 1rem;
        }

        .spec-cell .spec-label {
            font-family: var(--font-mono);
            font-size: 0.6875rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }

        .spec-cell .spec-val {
            font-family: var(--font-mono);
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* 4 Hardware Panels Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
            gap: 1.25rem;
        }

        .panel {
            background-color: var(--bg-panel);
            border: 1px solid var(--border-dim);
            border-radius: 6px;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .panel-title {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            font-weight: 600;
        }

        .tag-status {
            font-family: var(--font-mono);
            font-size: 0.6875rem;
            font-weight: 600;
            padding: 0.15rem 0.5rem;
            border-radius: 3px;
            border: 1px solid transparent;
        }

        .tag-status.ok {
            background-color: var(--accent-green-bg);
            color: var(--accent-green);
            border-color: rgba(16, 185, 129, 0.2);
        }

        .tag-status.warning {
            background-color: var(--accent-amber-bg);
            color: var(--accent-amber);
            border-color: rgba(245, 158, 11, 0.2);
        }

        .tag-status.critical {
            background-color: var(--accent-rose-bg);
            color: var(--accent-rose);
            border-color: rgba(244, 63, 94, 0.2);
        }

        .metric-primary {
            display: flex;
            align-items: baseline;
            gap: 0.375rem;
        }

        .stat-huge {
            font-family: var(--font-mono);
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
            letter-spacing: -0.03em;
        }

        .stat-sub {
            font-family: var(--font-mono);
            font-size: 0.8125rem;
            color: var(--text-muted);
        }

        /* Segmented / Hairline Gauge Bar */
        .gauge-track {
            height: 4px;
            background-color: var(--bg-muted);
            border-radius: 2px;
            overflow: hidden;
        }

        .gauge-fill {
            height: 100%;
            background-color: var(--accent-cyan);
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        .gauge-fill.ok {
            background-color: var(--accent-green);
        }

        .gauge-fill.warning {
            background-color: var(--accent-amber);
        }

        .gauge-fill.critical {
            background-color: var(--accent-rose);
        }

        /* Metric key-value table inside panel */
        .kv-table {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            padding-top: 0.5rem;
            border-top: 1px solid var(--border-dim);
            font-family: var(--font-mono);
            font-size: 0.75rem;
        }

        .kv-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .kv-key {
            color: var(--text-muted);
        }

        .kv-val {
            color: var(--text-primary);
            font-weight: 600;
        }

        /* Rolling Probe Activity Stream */
        .stream-panel {
            background-color: var(--bg-panel);
            border: 1px solid var(--border-dim);
            border-radius: 6px;
            padding: 1.25rem;
        }

        .stream-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.875rem;
        }

        .stream-title {
            font-family: var(--font-mono);
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stream-sub {
            font-family: var(--font-mono);
            font-size: 0.6875rem;
            color: var(--text-muted);
        }

        .table-wrap {
            overflow-x: auto;
        }

        table.stream-table {
            width: 100%;
            border-collapse: collapse;
            font-family: var(--font-mono);
            font-size: 0.75rem;
            text-align: left;
        }

        table.stream-table th {
            color: var(--text-muted);
            font-weight: 500;
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid var(--border-dim);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.6875rem;
        }

        table.stream-table td {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid var(--border-dim);
            color: var(--text-secondary);
        }

        table.stream-table tr:last-child td {
            border-bottom: none;
        }

        table.stream-table tr:hover td {
            background-color: var(--bg-panel-elevated);
        }


        /* Footer */
        .footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: var(--font-mono);
            font-size: 0.6875rem;
            color: var(--text-muted);
            padding: 0.5rem 0.25rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            .specs-strip {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="layout">
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-brand">
                <span class="brand-indicator" aria-hidden="true"></span>
                <span class="brand-title">
                    <span class="pkg-author">kikopola/</span>monitoring-server
                </span>
                <span class="brand-tag">PORT 8989</span>
            </div>

            <div class="topbar-actions">
                <div class="badge-live">
                    <span class="live-dot" aria-hidden="true"></span>
                    <span id="label-live">PROBE: AKTIF</span>
                </div>
                <button type="button" class="btn-action" id="btn-reprobe" onclick="runProbe(true)">
                    <span>Re-probe</span>
                </button>
            </div>
        </header>

        <!-- System Specs Strip -->
        <section class="specs-strip" aria-label="Informasi Perangkat Keras">
            <div class="spec-cell">
                <div class="spec-label">HOSTNAME</div>
                <div class="spec-val" id="spec-host" title="{{ $metrics['system']['hostname'] }}">{{ $metrics['system']['hostname'] }}</div>
            </div>
            <div class="spec-cell">
                <div class="spec-label">SISTEM OPERASI</div>
                <div class="spec-val" id="spec-os" title="{{ $metrics['system']['os'] }}">{{ $metrics['system']['os'] }}</div>
            </div>
            <div class="spec-cell">
                <div class="spec-label">ALAMAT IP</div>
                <div class="spec-val" id="spec-ip">{{ $metrics['system']['server_ip'] }}</div>
            </div>
            <div class="spec-cell">
                <div class="spec-label">RUNTIME ENGINE</div>
                <div class="spec-val">PHP {{ $metrics['system']['php_version'] }} &bull; Lv {{ $metrics['system']['laravel_version'] }}</div>
            </div>
            <div class="spec-cell">
                <div class="spec-label">SYSTEM UPTIME</div>
                <div class="spec-val" id="spec-uptime">{{ $metrics['system']['uptime'] }}</div>
            </div>
        </section>

        <!-- Core 4-Panel Metrics Grid -->
        <main class="metrics-grid">
            <!-- CPU Panel -->
            <article class="panel">
                <div class="panel-header">
                    <span class="panel-title">PROCESSOR (CPU)</span>
                    <span class="tag-status {{ $metrics['cpu']['status'] }}" id="tag-cpu">{{ strtoupper($metrics['cpu']['status']) }}</span>
                </div>
                <div class="metric-primary">
                    <span class="stat-huge" id="val-cpu">{{ $metrics['cpu']['load_percentage'] }}</span>
                    <span class="stat-sub">% LOAD</span>
                </div>
                <div class="gauge-track">
                    <div class="gauge-fill {{ $metrics['cpu']['status'] }}" id="gauge-cpu" style="width: {{ min($metrics['cpu']['load_percentage'], 100) }}%;"></div>
                </div>
                <div class="kv-table">
                    <div class="kv-row">
                        <span class="kv-key">Beban Realtime</span>
                        <span class="kv-val" id="sub-cpu-load">{{ $metrics['cpu']['load_percentage'] }}%</span>
                    </div>
                    <div class="kv-row">
                        <span class="kv-key">Status Sensor</span>
                        <span class="kv-val" id="sub-cpu-state">{{ $metrics['cpu']['status'] === 'ok' ? 'Normal' : 'Tinggi' }}</span>
                    </div>
                </div>
            </article>

            <!-- RAM Panel -->
            <article class="panel">
                <div class="panel-header">
                    <span class="panel-title">PHYSICAL MEMORY (RAM)</span>
                    <span class="tag-status {{ $metrics['memory']['status'] }}" id="tag-ram">{{ strtoupper($metrics['memory']['status']) }}</span>
                </div>
                <div class="metric-primary">
                    <span class="stat-huge" id="val-ram">{{ $metrics['memory']['used_percentage'] }}</span>
                    <span class="stat-sub">% USED</span>
                </div>
                <div class="gauge-track">
                    <div class="gauge-fill {{ $metrics['memory']['status'] }}" id="gauge-ram" style="width: {{ $metrics['memory']['used_percentage'] }}%;"></div>
                </div>
                <div class="kv-table">
                    <div class="kv-row">
                        <span class="kv-key">Terpakai</span>
                        <span class="kv-val" id="sub-ram-used">{{ $metrics['memory']['used'] }}</span>
                    </div>
                    <div class="kv-row">
                        <span class="kv-key">Kapasitas Total</span>
                        <span class="kv-val" id="sub-ram-total">{{ $metrics['memory']['total'] }}</span>
                    </div>
                </div>
            </article>

            <!-- Storage Panel -->
            <article class="panel">
                <div class="panel-header">
                    <span class="panel-title">STORAGE (DISK)</span>
                    <span class="tag-status {{ $metrics['disk']['status'] }}" id="tag-disk">{{ strtoupper($metrics['disk']['status']) }}</span>
                </div>
                <div class="metric-primary">
                    <span class="stat-huge" id="val-disk">{{ $metrics['disk']['used_percentage'] }}</span>
                    <span class="stat-sub">% USED</span>
                </div>
                <div class="gauge-track">
                    <div class="gauge-fill {{ $metrics['disk']['status'] }}" id="gauge-disk" style="width: {{ $metrics['disk']['used_percentage'] }}%;"></div>
                </div>
                <div class="kv-table">
                    <div class="kv-row">
                        <span class="kv-key">Ruang Bebas</span>
                        <span class="kv-val" id="sub-disk-free">{{ $metrics['disk']['free'] }}</span>
                    </div>
                    <div class="kv-row">
                        <span class="kv-key">Total Partisi</span>
                        <span class="kv-val" id="sub-disk-total">{{ $metrics['disk']['total'] }}</span>
                    </div>
                </div>
            </article>

            <!-- Database Panel -->
            <article class="panel">
                <div class="panel-header">
                    <span class="panel-title">DATABASE (PDO)</span>
                    <span class="tag-status {{ $metrics['database']['status'] }}" id="tag-db">{{ strtoupper($metrics['database']['status']) }}</span>
                </div>
                <div class="metric-primary">
                    <span class="stat-huge" id="val-db-latency">{{ $metrics['database']['latency_ms'] !== null ? $metrics['database']['latency_ms'] : '0' }}</span>
                    <span class="stat-sub">MS LATENCY</span>
                </div>
                <div class="gauge-track">
                    <div class="gauge-fill {{ $metrics['database']['status'] }}" id="gauge-db" style="width: {{ $metrics['database']['connected'] ? '100' : '0' }}%;"></div>
                </div>
                <div class="kv-table">
                    <div class="kv-row">
                        <span class="kv-key">Status Koneksi</span>
                        <span class="kv-val" id="sub-db-status" style="color: {{ $metrics['database']['connected'] ? 'var(--accent-green)' : 'var(--accent-rose)' }};">
                            {{ $metrics['database']['connected'] ? 'CONNECTED' : 'DISCONNECTED' }}
                        </span>
                    </div>
                    <div class="kv-row">
                        <span class="kv-key">Driver / DB</span>
                        <span class="kv-val">{{ strtoupper($metrics['database']['driver']) }}</span>
                    </div>
                </div>
            </article>
        </main>

        <!-- Rolling Probe Activity Log -->
        <section class="stream-panel">
            <div class="stream-header">
                <div class="stream-title">
                    <span>ROLLING PROBE AUDIT STREAM</span>
                </div>
                <div class="stream-sub">INTERVAL: 3 DETIK (REAL-TIME)</div>
            </div>
            <div class="table-wrap">
                <table class="stream-table">
                    <thead>
                        <tr>
                            <th>TIMESTAMP</th>
                            <th>CPU</th>
                            <th>RAM</th>
                            <th>DISK BEBAS</th>
                            <th>DB LATENSI</th>
                            <th>STATUS PROBE</th>
                        </tr>
                    </thead>
                    <tbody id="stream-rows">
                        <tr>
                            <td>{{ date('H:i:s') }}</td>
                            <td>{{ $metrics['cpu']['load_percentage'] }}%</td>
                            <td>{{ $metrics['memory']['used_percentage'] }}% ({{ $metrics['memory']['used'] }})</td>
                            <td>{{ $metrics['disk']['free'] }}</td>
                            <td>{{ $metrics['database']['latency_ms'] !== null ? $metrics['database']['latency_ms'] . ' ms' : 'N/A' }}</td>
                            <td><span class="tag-status ok">HEALTHY</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>


        <!-- Footer -->
        <footer class="footer">
            <div>kikopola/monitoring-server &bull; Standalone Hardware Probe &bull; MIT License</div>
            <div id="footer-sync">Terakhir Diperiksa: {{ date('Y-m-d H:i:s') }}</div>
        </footer>
    </div>

    <!-- Live Polling & Telemetry Script -->
    <script>
        const API_URL = "{{ $apiEndpoint }}";
        const TOKEN = "{{ $token }}";
        const maxLogRows = 6;

        async function runProbe(isManual = false) {
            const btn = document.getElementById('btn-reprobe');
            if (isManual && btn) {
                btn.disabled = true;
                btn.innerText = 'Probing...';
            }

            try {
                let url = API_URL;
                if (TOKEN) {
                    url += (url.includes('?') ? '&' : '?') + 'token=' + encodeURIComponent(TOKEN);
                }

                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!res.ok) return;

                const data = await res.json();
                renderMetrics(data);
                appendStreamRow(data);

                const now = new Date();
                const timeStr = now.toISOString().replace('T', ' ').substring(0, 19);
                document.getElementById('footer-sync').innerText = 'Terakhir Diperiksa: ' + timeStr;
            } catch (err) {
                console.error("Probe fetch error:", err);
            } finally {
                if (isManual && btn) {
                    btn.disabled = false;
                    btn.innerText = 'Re-probe';
                }
            }
        }

        function renderMetrics(data) {
            if (data.system?.uptime) {
                document.getElementById('spec-uptime').innerText = data.system.uptime;
            }

            // CPU
            if (data.cpu) {
                document.getElementById('val-cpu').innerText = data.cpu.load_percentage;
                document.getElementById('sub-cpu-load').innerText = data.cpu.load_percentage + '%';
                document.getElementById('sub-cpu-state').innerText = data.cpu.status === 'ok' ? 'Normal' : 'Tinggi';
                const gauge = document.getElementById('gauge-cpu');
                gauge.style.width = Math.min(data.cpu.load_percentage, 100) + '%';
                gauge.className = 'gauge-fill ' + data.cpu.status;
                setTag('tag-cpu', data.cpu.status);
            }

            // RAM
            if (data.memory) {
                document.getElementById('val-ram').innerText = data.memory.used_percentage;
                document.getElementById('sub-ram-used').innerText = data.memory.used;
                document.getElementById('sub-ram-total').innerText = data.memory.total;
                const gauge = document.getElementById('gauge-ram');
                gauge.style.width = data.memory.used_percentage + '%';
                gauge.className = 'gauge-fill ' + data.memory.status;
                setTag('tag-ram', data.memory.status);
            }

            // Disk
            if (data.disk) {
                document.getElementById('val-disk').innerText = data.disk.used_percentage;
                document.getElementById('sub-disk-free').innerText = data.disk.free;
                document.getElementById('sub-disk-total').innerText = data.disk.total;
                const gauge = document.getElementById('gauge-disk');
                gauge.style.width = data.disk.used_percentage + '%';
                gauge.className = 'gauge-fill ' + data.disk.status;
                setTag('tag-disk', data.disk.status);
            }

            // Database
            if (data.database) {
                const latency = data.database.latency_ms !== null ? data.database.latency_ms : '0';
                document.getElementById('val-db-latency').innerText = latency;
                const dbStatus = document.getElementById('sub-db-status');
                dbStatus.innerText = data.database.connected ? 'CONNECTED' : 'DISCONNECTED';
                dbStatus.style.color = data.database.connected ? 'var(--accent-green)' : 'var(--accent-rose)';
                setTag('tag-db', data.database.status);
            }
        }

        function setTag(id, status) {
            const el = document.getElementById(id);
            if (!el) return;
            el.className = 'tag-status ' + status;
            el.innerText = status.toUpperCase();
        }

        function appendStreamRow(data) {
            const tbody = document.getElementById('stream-rows');
            if (!tbody) return;

            const now = new Date();
            const time = String(now.getHours()).padStart(2, '0') + ':' +
                         String(now.getMinutes()).padStart(2, '0') + ':' +
                         String(now.getSeconds()).padStart(2, '0');

            const cpu = data.cpu ? data.cpu.load_percentage + '%' : '-';
            const ram = data.memory ? data.memory.used_percentage + '% (' + data.memory.used + ')' : '-';
            const disk = data.disk ? data.disk.free : '-';
            const latency = (data.database && data.database.latency_ms !== null) ? (data.database.latency_ms + ' ms') : 'N/A';
            const isHealthy = (!data.cpu || data.cpu.status === 'ok') && (!data.memory || data.memory.status !== 'critical') && (!data.database || data.database.connected);

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${time}</td>
                <td>${cpu}</td>
                <td>${ram}</td>
                <td>${disk}</td>
                <td>${latency}</td>
                <td><span class="tag-status ${isHealthy ? 'ok' : 'warning'}">${isHealthy ? 'HEALTHY' : 'DEGRADED'}</span></td>
            `;

            tbody.insertBefore(row, tbody.firstChild);

            while (tbody.children.length > maxLogRows) {
                tbody.removeChild(tbody.lastChild);
            }
        }


        // Live polling every 3 seconds
        setInterval(runProbe, 3000);
    </script>
</body>
</html>
