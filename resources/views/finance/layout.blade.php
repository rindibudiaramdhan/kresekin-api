<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Finance Panel' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f6f8fb; color: #172033; }
        .app-shell { min-height: 100vh; }
        .sidebar { width: 260px; background: #111827; color: #e5e7eb; }
        .sidebar a { color: #cbd5e1; border-radius: 8px; }
        .sidebar a.active, .sidebar a:hover { background: #15803d; color: #fff; }
        .content { max-width: 1180px; }
        .metric-card { border: 0; border-radius: 8px; }
        .table > :not(caption) > * > * { padding: 1rem; }
        @media (max-width: 991.98px) {
            .sidebar { width: 100%; }
            .app-shell { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="d-flex app-shell">
        @auth
            @if (auth()->user()?->role === \App\Models\User::ROLE_FINANCE)
                <aside class="sidebar p-4">
                    <div class="h4 mb-4">Finance</div>
                    <nav class="nav flex-column gap-2">
                        <a class="nav-link @if(request()->routeIs('finance.dashboard')) active @endif" href="{{ route('finance.dashboard') }}">Dashboard</a>
                        <a class="nav-link @if(request()->routeIs('finance.transactions.*')) active @endif" href="{{ route('finance.transactions.index') }}">Transaksi</a>
                    </nav>
                    <form method="POST" action="{{ route('finance.logout') }}" class="mt-4">
                        @csrf
                        <button class="btn btn-outline-light w-100" type="submit">Logout</button>
                    </form>
                </aside>
            @endif
        @endauth
        <main class="flex-grow-1 p-4">
            <div class="content mx-auto">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
