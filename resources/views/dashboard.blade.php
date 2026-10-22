<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>Server Monitor &bull; {{ $metrics['system']['hostname'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-canvas: #09090b;
            --bg-card: #0f0f12;
            --bg-card-hover: #141418;
            --bg-subtle: #18181b;
            --bg-muted: #27272a;
            --border-card: rgba(255, 255, 255, 0.08);
            --border-card-hover: rgba(255, 255, 255, 0.16);
            --border-divider: rgba(255, 255, 255, 0.05);
            --text-heading: #fafafa;
            --text-body: #a1a1aa;
            --text-muted: #71717a;
            --text-faint: #52525b;
            --color-emerald: #10b981;
            --color-emerald-glow: rgba(16, 185, 129, 0.15);
            --color-amber: #f59e0b;
            --color-amber-glow: rgba(245, 158, 11, 0.15);
            --color-rose: #f43f5e;
            --color-rose-glow: rgba(244, 63, 94, 0.15);
            --color-cyan: #06b6d4;
            --color-blue: #3b82f6;
            --font-sans: 'Geist', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-mono: 'Geist Mono', ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-canvas);
            color: var(--text-body);
            font-family: var(--font-sans);
            font-size: 0.875rem;
            line-height: 1.5;
            min-height: 100vh;
            padding: 2rem 1.5rem;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* 21st.dev Subtle Ambient Glow */
        .ambient-glow {
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 800px;
            height: 350px;
            background: radial-gradient(ellipse 60% 50% at 50% 0%, rgba(59, 130, 246, 0.04), transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        .dashboard-container {
            max-width: 1240px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            position: relative;
            z-index: 1;
        }

        /* 21st.dev Sleek Topbar Header */
        .app-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(180deg, rgba(24, 24, 27, 0.6) 0%, rgba(15, 15, 18, 0.8) 100%);
            border: 1px solid var(--border-card);
            backdrop-filter: blur(12px);
            border-radius: var(--radius-lg);
            padding: 0.875rem 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .brand-cluster {
            display: flex;
            align-items: center;
            gap: 0.875rem;
        }

        .brand-badge-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #18181b 0%, #0f0f12 100%);
            border: 1px solid var(--border-card);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-emerald);
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.1);
        }

        .brand-meta {
            display: flex;
            flex-direction: column;
        }

        .brand-title {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-heading);
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .brand-title .pkg-name {
            color: var(--text-heading);
        }

        .brand-title .pkg-server {
            color: var(--color-rose);
        }

        .version-pill {
            font-family: var(--font-mono);
            font-size: 0.6875rem;
            color: var(--text-muted);
            background-color: var(--bg-subtle);
            border: 1px solid var(--border-divider);
            padding: 0.1rem 0.4rem;
            border-radius: var(--radius-sm);
            font-weight: 500;
        }

        .brand-sub {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-family: var(--font-mono);
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* 21st.dev Pulse Pill */
        .live-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background-color: var(--bg-card);
            border: 1px solid var(--border-card);
            padding: 0.4rem 0.85rem;
            border-radius: 9999px;
            font-family: var(--font-mono);
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-heading);
        }

        .pulse-ring {
            position: relative;
            width: 8px;
            height: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pulse-core {
            width: 6px;
            height: 6px;
            background-color: var(--color-emerald);
            border-radius: 50%;
        }

        .pulse-wave {
            position: absolute;
            width: 100%;
            height: 100%;
            background-color: var(--color-emerald);
            border-radius: 50%;
            opacity: 0.75;
            animation: pulse-animation 2s cubic-bezier(0, 0, 0.2, 1) infinite;
        }

        @keyframes pulse-animation {
            0% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(2.2); opacity: 0; }
            100% { transform: scale(2.2); opacity: 0; }
        }

        /* 21st.dev Button */
        .btn-probe {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(180deg, #27272a 0%, #18181b 100%);
            border: 1px solid var(--border-card);
            color: var(--text-heading);
            padding: 0.425rem 0.95rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 500;
            font-family: var(--font-sans);
            cursor: pointer;
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .btn-probe:hover {
            border-color: var(--border-card-hover);
            background: linear-gradient(180deg, #3f3f46 0%, #27272a 100%);
            transform: translateY(-1px);
        }

        .btn-probe:active {
            transform: translateY(0);
        }

        .btn-logout {
            text-decoration: none;
            color: #fda4af;
            background: rgba(244, 63, 94, 0.12);
            border-color: rgba(244, 63, 94, 0.35);
        }

        .btn-logout:hover {
            background: rgba(244, 63, 94, 0.25);
            border-color: var(--color-rose);
            color: #fff;
        }

        .kbd-hint {
            font-family: var(--font-mono);
            font-size: 0.65rem;
            color: var(--text-muted);
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid var(--border-card);
            padding: 0.1rem 0.35rem;
            border-radius: 3px;
        }

        /* System Telemetry Specs Strip */
        .specs-ribbon {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1px;
            background-color: var(--border-card);
            border: 1px solid var(--border-card);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .spec-item {
            background-color: var(--bg-card);
            padding: 1rem 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            transition: background-color 0.15s ease;
        }

        .spec-item:hover {
            background-color: var(--bg-card-hover);
        }

        .spec-label {
            font-family: var(--font-mono);
            font-size: 0.6875rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .spec-value {
            font-family: var(--font-mono);
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-heading);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* 4-Card Hardware Telemetry Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
            gap: 1.25rem;
        }

        .card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 1.25rem;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .card:hover {
            border-color: var(--border-card-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-tagline {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-icon {
            width: 28px;
            height: 28px;
            background-color: var(--bg-subtle);
            border: 1px solid var(--border-card);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
        }

        .card-name {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-heading);
            letter-spacing: -0.01em;
        }

        /* 21st.dev Status Badges */
        .status-chip {
            font-family: var(--font-mono);
            font-size: 0.6875rem;
            font-weight: 600;
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            border: 1px solid transparent;
            letter-spacing: 0.02em;
        }

        .status-chip.ok {
            background-color: var(--color-emerald-glow);
            color: var(--color-emerald);
            border-color: rgba(16, 185, 129, 0.25);
        }

        .status-chip.warning {
            background-color: var(--color-amber-glow);
            color: var(--color-amber);
            border-color: rgba(245, 158, 11, 0.25);
        }

        .status-chip.critical {
            background-color: var(--color-rose-glow);
            color: var(--color-rose);
            border-color: rgba(244, 63, 94, 0.25);
        }

        .card-hero-stat {
            display: flex;
            align-items: baseline;
            gap: 0.35rem;
        }

        .stat-number {
            font-family: var(--font-mono);
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
            color: var(--text-heading);
            letter-spacing: -0.04em;
        }

        .stat-unit {
            font-family: var(--font-mono);
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-muted);
        }

        /* 21st.dev Segmented Meter Bar */
        .progress-track {
            height: 6px;
            background-color: var(--bg-subtle);
            border-radius: 3px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar {
            height: 100%;
            background-color: var(--color-cyan);
            border-radius: 3px;
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .progress-bar.ok {
            background-color: var(--color-emerald);
        }

        .progress-bar.warning {
            background-color: var(--color-amber);
        }

        .progress-bar.critical {
            background-color: var(--color-rose);
        }

        .card-details-table {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            padding-top: 0.875rem;
            border-top: 1px solid var(--border-divider);
            font-family: var(--font-mono);
            font-size: 0.75rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .detail-key {
            color: var(--text-muted);
        }

        .detail-val {
            color: var(--text-heading);
            font-weight: 500;
        }

        /* Real-time Telemetry Stream Panel */
        .stream-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .stream-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .stream-title-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stream-icon {
            width: 8px;
            height: 8px;
            background-color: var(--color-cyan);
            border-radius: 2px;
        }

        .stream-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-heading);
            letter-spacing: -0.01em;
        }

        .stream-counter {
            font-family: var(--font-mono);
            font-size: 0.6875rem;
            color: var(--text-muted);
            background-color: var(--bg-subtle);
            border: 1px solid var(--border-card);
            padding: 0.2rem 0.5rem;
            border-radius: var(--radius-sm);
        }

        .stream-table-container {
            overflow-x: auto;
        }

        table.telemetry-table {
            width: 100%;
            border-collapse: collapse;
            font-family: var(--font-mono);
            font-size: 0.75rem;
            text-align: left;
        }

        table.telemetry-table th {
            color: var(--text-muted);
            font-weight: 500;
            padding: 0.625rem 0.875rem;
            border-bottom: 1px solid var(--border-card);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.6875rem;
            background-color: rgba(255, 255, 255, 0.01);
        }

        table.telemetry-table td {
            padding: 0.625rem 0.875rem;
            border-bottom: 1px solid var(--border-divider);
            color: var(--text-body);
            transition: background-color 0.1s ease;
        }

        table.telemetry-table tr:hover td {
            background-color: var(--bg-subtle);
            color: var(--text-heading);
        }

        table.telemetry-table tr:last-child td {
            border-bottom: none;
        }

        /* Footer */
        .dashboard-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0.25rem;
            font-family: var(--font-mono);
            font-size: 0.6875rem;
            color: var(--text-muted);
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .footer-brand a {
            color: var(--text-body);
            text-decoration: none;
        }

        .footer-brand a:hover {
            color: var(--text-heading);
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            .specs-ribbon {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="ambient-glow" aria-hidden="true"></div>

    <div class="dashboard-container">
        <!-- 21st.dev Navigation Topbar -->
        <header class="app-header">
            <div class="brand-cluster">
                <div class="brand-badge-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                        <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                        <line x1="6" y1="6" x2="6.01" y2="6"></line>
                        <line x1="6" y1="18" x2="6.01" y2="18"></line>
                    </svg>
                </div>
                <div class="brand-meta">
                    <div class="brand-title">
                        <span class="pkg-name">kikopola/monitoring-</span><span class="pkg-server">server</span>
                        <span class="version-pill">v1.0.0</span>
                    </div>
                    <div class="brand-sub">PORT 8989 &bull; DEDICATED PROBE AGENT</div>
                </div>
            </div>

            <div class="header-controls">
                <div class="live-indicator">
                    <div class="pulse-ring">
                        <div class="pulse-wave"></div>
                        <div class="pulse-core"></div>
                    </div>
                    <span id="label-live">LIVE PROBE (3s)</span>
                </div>
                <button type="button" class="btn-probe" id="btn-reprobe" onclick="runProbe(true)" title="Jalankan pemeriksaan ulang langsung">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <polyline points="1 20 1 14 7 14"></polyline>
                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                    </svg>
                    <span>Re-probe</span>
                    <span class="kbd-hint">R</span>
                </button>
                @if($isPasswordProtected ?? false)
                <a href="{{ url(trim(config('monitoring-server.dashboard_path', 'monitoring'), '/') . '/logout') }}" class="btn-probe btn-logout" title="Kunci Dashboard / Keluar">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Logout</span>
                </a>
                @endif
            </div>
        </header>

        <!-- System Specifications Ribbon -->
        <section class="specs-ribbon" aria-label="Informasi Perangkat Keras Mesin">
            <div class="spec-item">
                <span class="spec-label">HOSTNAME</span>
                <span class="spec-value" id="spec-host" title="{{ $metrics['system']['hostname'] }}">{{ $metrics['system']['hostname'] }}</span>
            </div>
            <div class="spec-item">
                <span class="spec-label">SISTEM OPERASI</span>
                <span class="spec-value" id="spec-os" title="{{ $metrics['system']['os'] }}">{{ $metrics['system']['os'] }}</span>
            </div>
            <div class="spec-item">
                <span class="spec-label">ALAMAT IP</span>
                <span class="spec-value" id="spec-ip">{{ $metrics['system']['server_ip'] }}</span>
            </div>
            <div class="spec-item">
                <span class="spec-label">RUNTIME ENGINE</span>
                <span class="spec-value">PHP {{ $metrics['system']['php_version'] }} &bull; Lv {{ $metrics['system']['laravel_version'] }}</span>
            </div>
            <div class="spec-item">
                <span class="spec-label">UPTIME SERVER</span>
                <span class="spec-value" id="spec-uptime">{{ $metrics['system']['uptime'] }}</span>
            </div>
        </section>

        <!-- 4 Hardware Metrics Grid -->
        <main class="cards-grid">
            <!-- CPU Panel -->
            <article class="card">
                <div class="card-header">
                    <div class="card-tagline">
                        <div class="card-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="4" y="4" width="16" height="16" rx="2"></rect>
                                <rect x="9" y="9" width="6" height="6"></rect>
                                <line x1="9" y1="1" x2="9" y2="4"></line>
                                <line x1="15" y1="1" x2="15" y2="4"></line>
                                <line x1="9" y1="20" x2="9" y2="23"></line>
                                <line x1="15" y1="20" x2="15" y2="23"></line>
                                <line x1="20" y1="9" x2="23" y2="9"></line>
                                <line x1="20" y1="14" x2="23" y2="14"></line>
                                <line x1="1" y1="9" x2="4" y2="9"></line>
                                <line x1="1" y1="14" x2="4" y2="14"></line>
                            </svg>
                        </div>
                        <span class="card-name">Processor (CPU)</span>
                    </div>
                    <span class="status-chip {{ $metrics['cpu']['status'] }}" id="tag-cpu">{{ strtoupper($metrics['cpu']['status']) }}</span>
                </div>

                <div class="card-hero-stat">
                    <span class="stat-number" id="val-cpu">{{ $metrics['cpu']['load_percentage'] }}</span>
                    <span class="stat-unit">% LOAD</span>
                </div>

                <div class="progress-track" role="progressbar" aria-valuenow="{{ $metrics['cpu']['load_percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar {{ $metrics['cpu']['status'] }}" id="bar-cpu" style="width: {{ min($metrics['cpu']['load_percentage'], 100) }}%;"></div>
                </div>

                <div class="card-details-table">
                    <div class="detail-row">
                        <span class="detail-key">Total Cores</span>
                        <span class="detail-val" id="sub-cpu-cores">{{ $metrics['cpu']['cores'] ?? 1 }} Cores</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-key">Beban Realtime</span>
                        <span class="detail-val" id="sub-cpu-load">{{ $metrics['cpu']['load_percentage'] }}%</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-key">Status Beban</span>
                        <span class="detail-val" id="sub-cpu-state">{{ $metrics['cpu']['status'] === 'ok' ? 'Normal' : 'Tinggi' }}</span>
                    </div>
                </div>
            </article>

            <!-- Memory RAM Panel -->
            <article class="card">
                <div class="card-header">
                    <div class="card-tagline">
                        <div class="card-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 19v-3"></path>
                                <path d="M10 19v-3"></path>
                                <path d="M14 19v-3"></path>
                                <path d="M18 19v-3"></path>
                                <path d="M2 5h20v10H2z"></path>
                            </svg>
                        </div>
                        <span class="card-name">Memory (RAM)</span>
                    </div>
                    <span class="status-chip {{ $metrics['memory']['status'] }}" id="tag-ram">{{ strtoupper($metrics['memory']['status']) }}</span>
                </div>

                <div class="card-hero-stat">
                    <span class="stat-number" id="val-ram">{{ $metrics['memory']['used_percentage'] }}</span>
                    <span class="stat-unit">% USED</span>
                </div>

                <div class="progress-track" role="progressbar" aria-valuenow="{{ $metrics['memory']['used_percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar {{ $metrics['memory']['status'] }}" id="bar-ram" style="width: {{ $metrics['memory']['used_percentage'] }}%;"></div>
                </div>

                <div class="card-details-table">
                    <div class="detail-row">
                        <span class="detail-key">RAM Terpakai</span>
                        <span class="detail-val" id="sub-ram-used">{{ $metrics['memory']['used'] }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-key">Total Fisik</span>
                        <span class="detail-val" id="sub-ram-total">{{ $metrics['memory']['total'] }}</span>
                    </div>
                </div>
            </article>

            <!-- Storage Panel -->
            <article class="card">
                <div class="card-header">
                    <div class="card-tagline">
                        <div class="card-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                                <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                                <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                            </svg>
                        </div>
                        <span class="card-name">Storage (Disk)</span>
                    </div>
                    <span class="status-chip {{ $metrics['disk']['status'] }}" id="tag-disk">{{ strtoupper($metrics['disk']['status']) }}</span>
                </div>

                <div class="card-hero-stat">
                    <span class="stat-number" id="val-disk">{{ $metrics['disk']['used_percentage'] }}</span>
                    <span class="stat-unit">% USED</span>
                </div>

                <div class="progress-track" role="progressbar" aria-valuenow="{{ $metrics['disk']['used_percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar {{ $metrics['disk']['status'] }}" id="bar-disk" style="width: {{ $metrics['disk']['used_percentage'] }}%;"></div>
                </div>

                <div class="card-details-table">
                    <div class="detail-row">
                        <span class="detail-key">Ruang Bebas</span>
                        <span class="detail-val" id="sub-disk-free">{{ $metrics['disk']['free'] }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-key">Total Partisi</span>
                        <span class="detail-val" id="sub-disk-total">{{ $metrics['disk']['total'] }}</span>
                    </div>
                </div>
            </article>

            <!-- Database Panel -->
            <article class="card">
                <div class="card-header">
                    <div class="card-tagline">
                        <div class="card-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2v20"></path>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                        <span class="card-name">Database (PDO)</span>
                    </div>
                    <span class="status-chip {{ $metrics['database']['status'] }}" id="tag-db">{{ strtoupper($metrics['database']['status']) }}</span>
                </div>

                <div class="card-hero-stat">
                    <span class="stat-number" id="val-db-latency">{{ $metrics['database']['latency_ms'] !== null ? $metrics['database']['latency_ms'] : '0' }}</span>
                    <span class="stat-unit">MS LATENCY</span>
                </div>

                <div class="progress-track">
                    <div class="progress-bar {{ $metrics['database']['status'] }}" id="bar-db" style="width: {{ $metrics['database']['connected'] ? '100' : '0' }}%;"></div>
                </div>

                <div class="card-details-table">
                    <div class="detail-row">
                        <span class="detail-key">Status Koneksi</span>
                        <span class="detail-val" id="sub-db-status" style="color: {{ $metrics['database']['connected'] ? 'var(--color-emerald)' : 'var(--color-rose)' }};">
                            {{ $metrics['database']['connected'] ? 'CONNECTED' : 'DISCONNECTED' }}
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-key">Driver / Engine</span>
                        <span class="detail-val">{{ strtoupper($metrics['database']['driver']) }}</span>
                    </div>
                </div>
            </article>
        </main>

        <!-- Realtime Telemetry Stream Table -->
        <section class="stream-card">
            <div class="stream-top">
                <div class="stream-title-group">
                    <div class="stream-icon"></div>
                    <h2 class="stream-title">Live Telemetry Probe Stream</h2>
                </div>
                <div class="stream-counter">POLL: 3 DETIK (REAL-TIME)</div>
            </div>

            <div class="stream-table-container">
                <table class="telemetry-table">
                    <thead>
                        <tr>
                            <th>WAKTU</th>
                            <th>BEBAN CPU</th>
                            <th>MEMORI RAM</th>
                            <th>DISK BEBAS</th>
                            <th>LATENSI DB</th>
                            <th>KONDISI SISTEM</th>
                        </tr>
                    </thead>
                    <tbody id="stream-rows">
                        <tr>
                            <td>{{ date('H:i:s') }}</td>
                            <td>{{ $metrics['cpu']['load_percentage'] }}%</td>
                            <td>{{ $metrics['memory']['used_percentage'] }}% ({{ $metrics['memory']['used'] }})</td>
                            <td>{{ $metrics['disk']['free'] }}</td>
                            <td>{{ $metrics['database']['latency_ms'] !== null ? $metrics['database']['latency_ms'] . ' ms' : 'N/A' }}</td>
                            <td><span class="status-chip ok">HEALTHY</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Minimalist Clean Footer -->
        <footer class="dashboard-footer">
            <div class="footer-brand">
                <span>kikopola/monitoring-server</span> &bull; <span>Standalone Hardware Probe Agent</span> &bull; <span>MIT License</span>
            </div>
            <div id="footer-sync">Sinkronisasi: {{ date('Y-m-d H:i:s') }}</div>
        </footer>
    </div>

    <!-- Live Polling Script -->
    <script>
        const API_URL = @json($apiEndpoint);
        const TOKEN = @json($token);
        const maxLogRows = 8;

        async function runProbe(isManual = false) {
            const btn = document.getElementById('btn-reprobe');
            if (isManual && btn) {
                btn.disabled = true;
                btn.innerHTML = `<span>Probing...</span>`;
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
                document.getElementById('footer-sync').innerText = 'Sinkronisasi: ' + timeStr;
            } catch (err) {
                console.error("Probe error:", err);
            } finally {
                if (isManual && btn) {
                    btn.disabled = false;
                    btn.innerHTML = `
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <polyline points="1 20 1 14 7 14"></polyline>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                        </svg>
                        <span>Re-probe</span>
                        <span class="kbd-hint">R</span>
                    `;
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
                if (data.cpu.cores && document.getElementById('sub-cpu-cores')) {
                    document.getElementById('sub-cpu-cores').innerText = data.cpu.cores + ' Cores';
                }
                document.getElementById('sub-cpu-load').innerText = data.cpu.load_percentage + '%';
                document.getElementById('sub-cpu-state').innerText = data.cpu.status === 'ok' ? 'Normal' : 'Tinggi';
                const bar = document.getElementById('bar-cpu');
                bar.style.width = Math.min(data.cpu.load_percentage, 100) + '%';
                bar.className = 'progress-bar ' + data.cpu.status;
                setChip('tag-cpu', data.cpu.status);
            }

            // RAM
            if (data.memory) {
                document.getElementById('val-ram').innerText = data.memory.used_percentage;
                document.getElementById('sub-ram-used').innerText = data.memory.used;
                document.getElementById('sub-ram-total').innerText = data.memory.total;
                const bar = document.getElementById('bar-ram');
                bar.style.width = data.memory.used_percentage + '%';
                bar.className = 'progress-bar ' + data.memory.status;
                setChip('tag-ram', data.memory.status);
            }

            // Disk
            if (data.disk) {
                document.getElementById('val-disk').innerText = data.disk.used_percentage;
                document.getElementById('sub-disk-free').innerText = data.disk.free;
                document.getElementById('sub-disk-total').innerText = data.disk.total;
                const bar = document.getElementById('bar-disk');
                bar.style.width = data.disk.used_percentage + '%';
                bar.className = 'progress-bar ' + data.disk.status;
                setChip('tag-disk', data.disk.status);
            }

            // Database
            if (data.database) {
                const latency = data.database.latency_ms !== null ? data.database.latency_ms : '0';
                document.getElementById('val-db-latency').innerText = latency;
                const dbStatus = document.getElementById('sub-db-status');
                dbStatus.innerText = data.database.connected ? 'CONNECTED' : 'DISCONNECTED';
                dbStatus.style.color = data.database.connected ? 'var(--color-emerald)' : 'var(--color-rose)';
                setChip('tag-db', data.database.status);
            }
        }

        function setChip(id, status) {
            const el = document.getElementById(id);
            if (!el) return;
            el.className = 'status-chip ' + status;
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
                <td><span class="status-chip ${isHealthy ? 'ok' : 'warning'}">${isHealthy ? 'HEALTHY' : 'DEGRADED'}</span></td>
            `;

            tbody.insertBefore(row, tbody.firstChild);

            while (tbody.children.length > maxLogRows) {
                tbody.removeChild(tbody.lastChild);
            }
        }

        // Shortcut keyboard 'R' untuk Re-probe
        window.addEventListener('keydown', (e) => {
            if ((e.key === 'r' || e.key === 'R') && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
                runProbe(true);
            }
        });

        // Polling setiap 3 detik
        setInterval(runProbe, 3000);
    </script>
</body>
</html>
