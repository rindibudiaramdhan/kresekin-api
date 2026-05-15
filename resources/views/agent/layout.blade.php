<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Agent Panel' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f6f8fb; color: #172033; }
        .panel-shell { max-width: 1200px; }
        .metric-card { border: 0; border-radius: 8px; }
        .nav-pills .nav-link { color: #475569; border-radius: 8px; }
        .nav-pills .nav-link.active { background: #15803d; }
        .table > :not(caption) > * > * { padding: 1rem; }
    </style>
</head>
<body class="min-vh-100">
    <div class="container py-4 panel-shell">
        @auth
            @if (auth()->user()?->role === \App\Models\User::ROLE_AGENT)
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                    <nav class="nav nav-pills gap-2">
                        <a class="nav-link @if(request()->routeIs('agent.dashboard')) active @endif" href="{{ route('agent.dashboard') }}">Dashboard</a>
                        <a class="nav-link @if(request()->routeIs('agent.sellers.*')) active @endif" href="{{ route('agent.sellers.index') }}">Seller</a>
                        <a class="nav-link @if(request()->routeIs('agent.tenants.*')) active @endif" href="{{ route('agent.tenants.index') }}">Tenant</a>
                        <a class="nav-link @if(request()->routeIs('agent.withdrawals.*')) active @endif" href="{{ route('agent.withdrawals.index') }}">Pencairan</a>
                        <a class="nav-link @if(request()->routeIs('agent.profile.*')) active @endif" href="{{ route('agent.profile.edit') }}">Profil</a>
                    </nav>
                    <form method="POST" action="{{ route('agent.logout') }}">
                        @csrf
                        <button class="btn btn-outline-danger" type="submit">Logout</button>
                    </form>
                </div>
            @endif
        @endauth

        @yield('content')
    </div>
</body>
</html>
