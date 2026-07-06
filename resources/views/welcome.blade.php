<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->company_name }}</title>
    <style>
        :root {
            color-scheme: light dark;
            --surface: #fcfcfb;
            --page: #f9f9f7;
            --ink: #0b0b0b;
            --ink-secondary: #52514e;
            --border: rgba(11, 11, 11, 0.10);
            --primary: #2a78d6;
            --primary-ink: #ffffff;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --surface: #1a1a19;
                --page: #0d0d0d;
                --ink: #ffffff;
                --ink-secondary: #c3c2b7;
                --border: rgba(255, 255, 255, 0.10);
                --primary: #3987e5;
            }
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--page);
            color: var(--ink);
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 420px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 40px 32px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .logo {
            max-height: 64px;
            margin-bottom: 16px;
        }
        h1 {
            font-size: 20px;
            margin: 0 0 4px;
        }
        .tagline {
            color: var(--ink-secondary);
            font-size: 14px;
            margin: 0 0 32px;
        }
        .actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .btn {
            display: block;
            padding: 12px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            transition: opacity 0.15s ease;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .btn-primary {
            background: var(--primary);
            color: var(--primary-ink);
        }
        .btn-secondary {
            background: transparent;
            color: var(--ink);
            border: 1px solid var(--border);
        }
        .footer {
            margin-top: 28px;
            font-size: 12px;
            color: var(--ink-secondary);
        }
    </style>
</head>
<body>
    <div class="card">
        @if ($settings->logoUrl())
            <img class="logo" src="{{ $settings->logoUrl() }}" alt="{{ $settings->company_name }} logo">
        @endif
        <h1>{{ $settings->company_name }}</h1>
        @if ($settings->tagline)
            <p class="tagline">{{ $settings->tagline }}</p>
        @else
            <p class="tagline">Cement Distribution Management</p>
        @endif

        <div class="actions">
            <a class="btn btn-primary" href="{{ url('/admin') }}">Back Office Login</a>
            <a class="btn btn-secondary" href="{{ url('/rep') }}">Sales Rep Login</a>
        </div>

        <p class="footer">&copy; {{ now()->year }} {{ $settings->company_name }}</p>
    </div>
</body>
</html>
