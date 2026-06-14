<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Finance Panel' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --finance-teal: #08bdc9;
            --finance-ink: #141820;
            --finance-muted: #54627a;
            --finance-line: #d9e0ec;
            --finance-bg: #f7f8fb;
        }

        body {
            background: var(--finance-bg);
            color: var(--finance-ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .app-shell {
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: #fff;
            border-right: 1px solid var(--finance-line);
        }

        .brand-text {
            color: var(--finance-teal);
            font-weight: 800;
            letter-spacing: 0;
        }

        .brand-text span {
            color: #f6a800;
        }

        .sidebar .nav-link {
            align-items: center;
            color: #53627d;
            display: flex;
            font-weight: 700;
            gap: .75rem;
            min-height: 44px;
            padding: .75rem 1rem;
            position: relative;
        }

        .sidebar .nav-link.active {
            background: #eef0f4;
            color: var(--finance-teal);
        }

        .sidebar .nav-link.active::after {
            background: var(--finance-teal);
            bottom: 0;
            content: "";
            position: absolute;
            right: 0;
            top: 0;
            width: 4px;
        }

        .sidebar-icon {
            align-items: center;
            display: inline-flex;
            height: 24px;
            justify-content: center;
            width: 24px;
        }

        .topbar {
            background: #fff;
            border-bottom: 1px solid #e7ebf2;
            min-height: 72px;
        }

        .content {
            max-width: 1160px;
        }

        .admin-title {
            color: var(--finance-teal);
            font-size: 1.35rem;
            font-weight: 800;
        }

        .profile-pill {
            background: #f0f1f3;
            border-radius: 999px;
            color: #2e3037;
            font-size: .875rem;
            font-weight: 700;
            padding: .45rem .9rem .45rem .45rem;
        }

        .avatar-dot {
            align-items: center;
            background: var(--finance-teal);
            border-radius: 50%;
            color: #fff;
            display: inline-flex;
            height: 30px;
            justify-content: center;
            width: 30px;
        }

        .finance-card {
            background: #fff;
            border: 1px solid var(--finance-line);
            border-radius: 12px;
            box-shadow: 0 10px 24px rgba(31, 41, 55, .06);
        }

        .metric-label {
            color: #4b5365;
            font-size: .8rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .metric-value {
            font-size: 1.9rem;
            font-weight: 800;
            letter-spacing: 0;
            line-height: 1.15;
        }

        .soft-icon {
            align-items: center;
            border-radius: 8px;
            display: inline-flex;
            height: 46px;
            justify-content: center;
            width: 46px;
        }

        .soft-icon.success { background: #80efb4; color: #065f46; }
        .soft-icon.primary { background: #dbe2ff; color: #19499a; }
        .soft-icon.warning { background: #fff0bd; color: #d97706; }

        .btn-teal {
            --bs-btn-bg: var(--finance-teal);
            --bs-btn-border-color: var(--finance-teal);
            --bs-btn-color: #fff;
            --bs-btn-hover-bg: #06aab5;
            --bs-btn-hover-border-color: #06aab5;
            --bs-btn-hover-color: #fff;
            border-radius: 8px;
            font-weight: 700;
        }

        .finance-table {
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 0;
        }

        .finance-table thead th {
            background: #eef0f3;
            color: #53617a;
            font-size: .76rem;
            font-weight: 800;
            letter-spacing: .09em;
            padding: 1.45rem 1.5rem;
            text-transform: uppercase;
        }

        .finance-table tbody td {
            border-color: #edf0f4;
            padding: 2rem 1.5rem;
            vertical-align: middle;
        }

        .status-pill {
            border-radius: 999px;
            display: inline-flex;
            font-size: .78rem;
            font-weight: 800;
            line-height: 1;
            padding: .45rem .9rem;
        }

        .status-success { background: #d9f6ea; color: #17634f; }
        .status-info { background: #cfe0ff; color: #667085; }
        .status-process { background: #ffd64d; color: #6f5300; }
        .status-danger { background: #ffd9d6; color: #d91f1f; }

        @media (max-width: 991.98px) {
            .app-shell { flex-direction: column; }
            .sidebar { width: 100%; }
            .sidebar .nav { flex-direction: row; overflow-x: auto; }
            .sidebar-footer { display: none; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="d-flex app-shell">
        @auth
            @if (auth()->user()?->role === \App\Models\User::ROLE_FINANCE)
                <aside class="sidebar d-flex flex-column p-4">
                    <div class="mb-5">
                        <div class="brand-text fs-5">Kresek<span>.in</span></div>
                        <div class="small fw-semibold text-secondary">Management Portal</div>
                    </div>
                    <nav class="nav flex-column gap-1">
                        <a class="nav-link @if(request()->routeIs('finance.dashboard')) active @endif" href="{{ route('finance.dashboard') }}">
                            <span class="sidebar-icon">▦</span>
                            Dashboard
                        </a>
                        <a class="nav-link @if(request()->routeIs('finance.transactions.*')) active @endif" href="{{ route('finance.transactions.index') }}">
                            <span class="sidebar-icon">▣</span>
                            Finance
                        </a>
                    </nav>
                    <div class="sidebar-footer mt-auto">
                        <a class="nav-link" href="#">
                            <span class="sidebar-icon">?</span>
                            Support
                        </a>
                        <form method="POST" action="{{ route('finance.logout') }}" class="mt-2">
                            @csrf
                            <button class="btn btn-link nav-link text-danger fw-bold p-0" type="submit">
                                <span class="sidebar-icon">↳</span>
                                Logout
                            </button>
                        </form>
                    </div>
                </aside>
            @endif
        @endauth
        <main class="flex-grow-1">
            <header class="topbar d-flex align-items-center justify-content-between px-4 px-lg-5">
                <div class="d-flex align-items-center gap-4">
                    <div class="admin-title">Admin Views</div>
                    <div class="vr d-none d-sm-block"></div>
                </div>
                @auth
                    <div class="d-flex align-items-center gap-3">
                        <span class="fs-5">♢</span>
                        <div class="profile-pill d-flex align-items-center gap-2">
                            <span class="avatar-dot">●</span>
                            {{ auth()->user()?->name ?? 'Finance Administrator' }}
                        </div>
                    </div>
                @endauth
            </header>
            <div class="content mx-auto px-4 px-lg-5 py-5">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
