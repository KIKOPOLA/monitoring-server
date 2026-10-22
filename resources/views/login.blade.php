<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &bull; Server Monitoring Protection</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-canvas: #09090b;
            --bg-card: rgba(15, 15, 18, 0.85);
            --border-card: rgba(255, 255, 255, 0.08);
            --border-card-hover: rgba(255, 255, 255, 0.16);
            --text-heading: #fafafa;
            --text-body: #a1a1aa;
            --text-muted: #71717a;
            --color-emerald: #10b981;
            --color-emerald-glow: rgba(16, 185, 129, 0.18);
            --color-rose: #f43f5e;
            --font-sans: 'Geist', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-mono: 'Geist Mono', ui-monospace, SFMono-Regular, monospace;
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
        }

        .ambient-glow {
            position: fixed;
            top: 20%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 500px;
            height: 400px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.08) 0%, rgba(6, 182, 212, 0.04) 40%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        .login-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 400px;
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-card);
            border-radius: 16px;
            padding: 2.25rem 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
        }

        .brand-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .icon-shield {
            width: 48px;
            height: 48px;
            margin: 0 auto 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(6, 182, 212, 0.1) 100%);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--color-emerald);
            box-shadow: 0 0 24px var(--color-emerald-glow);
        }

        .brand-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-heading);
            letter-spacing: -0.02em;
            margin-bottom: 0.35rem;
        }

        .brand-subtitle {
            font-size: 0.8125rem;
            color: var(--text-muted);
        }

        .alert-error {
            background: rgba(244, 63, 94, 0.1);
            border: 1px solid rgba(244, 63, 94, 0.3);
            color: #fda4af;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.8125rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-heading);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            background: #141418;
            border: 1px solid var(--border-card);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            color: var(--text-heading);
            font-family: inherit;
            outline: none;
            transition: all 0.15s ease;
        }

        .form-input:focus {
            border-color: var(--color-emerald);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
            background: #18181c;
        }

        .btn-toggle-pw {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.25rem;
            display: flex;
            align-items: center;
        }

        .btn-toggle-pw:hover {
            color: var(--text-heading);
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
            border: 1px solid #10b981;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .btn-submit:hover {
            background: linear-gradient(180deg, #34d399 0%, #10b981 100%);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35);
            transform: translateY(-1px);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .footer-note {
            text-align: center;
            margin-top: 1.75rem;
            font-size: 0.75rem;
            color: var(--text-muted);
            font-family: var(--font-mono);
        }
    </style>
</head>
<body>
    <div class="ambient-glow" aria-hidden="true"></div>

    <div class="login-card">
        <div class="brand-header">
            <div class="icon-shield">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <h1 class="brand-title">Akses Monitoring</h1>
            <p class="brand-subtitle">Dashboard ini dikunci. Masukkan password untuk melanjutkan.</p>
        </div>

        @if(session('error'))
        <div class="alert-error" role="alert">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
        @endif

        <form action="{{ url(trim($dashboardPath, '/') . '/login') }}" method="POST">
            @csrf

            @if($hasUsername ?? false)
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-input" 
                    value="{{ old('username') }}"
                    required 
                    autofocus 
                    placeholder="admin"
                >
            </div>
            @endif

            <div class="form-group">
                <label for="password" class="form-label">Password Akses</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        required 
                        @if(!($hasUsername ?? false)) autofocus @endif
                        placeholder="••••••••••••"
                    >
                    <button type="button" class="btn-toggle-pw" onclick="togglePasswordVisibility()" title="Lihat Password">
                        <svg id="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <span>Buka Dashboard</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
        </form>

        <div class="footer-note">
            <span>kikopola/monitoring-server</span>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        }
    </script>
</body>
</html>
