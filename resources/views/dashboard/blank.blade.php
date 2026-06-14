<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $title ?? 'Dashboard' }} - {{ config('app.name', 'Kresek.in') }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: #151922;
            background: #f8fafc;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .dashboard-shell {
            min-height: 100vh;
            display: flex;
        }

        .dashboard-main {
            flex: 1;
            min-width: 0;
            background: #f8fafc;
        }

        @media (max-width: 860px) {
            .dashboard-shell {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-shell">
        <x-dashboard.sidebar :role="$role ?? 'agent'" :active="$active ?? 'dashboard'" />

        <main class="dashboard-main" aria-label="{{ $title ?? 'Dashboard' }}"></main>
    </div>
</body>
</html>
