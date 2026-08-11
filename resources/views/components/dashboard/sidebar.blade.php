@props([
    'active' => 'dashboard',
    'role' => 'agent',
])

@php
    $dashboardRoute = match ($role) {
        'finance' => route('finance.dashboard'),
        'owner' => route('owner.monitoring'),
        default => route('agent.dashboard'),
    };
    $items = [
        [
            'key' => $role === 'owner' ? 'monitoring' : 'dashboard',
            'label' => $role === 'owner' ? 'Online Monitoring' : 'Dashboard',
            'href' => $dashboardRoute,
            'icon' => 'dashboard',
        ],
    ];

    if ($role === 'finance') {
        $items[] = [
            'key' => 'finance',
            'label' => 'Finance',
            'href' => route('finance.finance'),
            'icon' => 'finance',
        ];
    } elseif ($role !== 'owner') {
        $items = [
            ...$items,
            [
                'key' => 'umkm',
                'label' => 'UMKM Binaan',
                'href' => '#',
                'icon' => 'umkm',
            ],
            [
                'key' => 'withdrawals',
                'label' => 'Pencairan Dana',
                'href' => route('agent.finance'),
                'icon' => 'finance',
            ],
            [
                'key' => 'settings',
                'label' => 'Pengaturan',
                'href' => '#',
                'icon' => 'settings',
            ],
        ];
    }
@endphp

@once
    <style>
        .dashboard-sidebar {
            width: 300px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #d7dce7;
            background: #ffffff;
            box-shadow: 8px 0 28px rgba(16, 24, 40, .06);
            padding: 34px 24px 28px;
        }

        .dashboard-sidebar__brand {
            display: grid;
            gap: 6px;
            padding: 0 10px;
        }

        .dashboard-sidebar__logo {
            width: 132px;
            height: auto;
            display: block;
        }

        .dashboard-sidebar__subtitle {
            color: #54627d;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: .01em;
        }

        .dashboard-sidebar__nav {
            display: grid;
            gap: 12px;
            margin-top: 38px;
        }

        .dashboard-sidebar__item {
            position: relative;
            min-height: 58px;
            display: flex;
            align-items: center;
            gap: 16px;
            color: #55627d;
            border-radius: 0;
            padding: 0 18px;
            text-decoration: none;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: .01em;
        }

        .dashboard-sidebar__item:hover,
        .dashboard-sidebar__item:focus-visible {
            color: #11bec8;
            background: #f4f6f9;
            text-decoration: none;
            outline: 0;
        }

        .dashboard-sidebar__item.is-active {
            color: #11bec8;
            background: #f4f6f9;
        }

        .dashboard-sidebar__item.is-active::after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            background: #11bec8;
        }

        .dashboard-sidebar__icon {
            width: 28px;
            height: 28px;
            flex: 0 0 auto;
            color: currentColor;
        }

        .dashboard-sidebar__bottom {
            display: grid;
            gap: 18px;
            margin-top: auto;
            padding: 0 10px;
        }

        .dashboard-sidebar__utility,
        .dashboard-sidebar__logout {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            gap: 16px;
            border: 0;
            background: transparent;
            color: #55627d;
            padding: 0;
            text-decoration: none;
            cursor: pointer;
            font: inherit;
            font-size: 17px;
            font-weight: 800;
            letter-spacing: .01em;
        }

        .dashboard-sidebar__utility:hover,
        .dashboard-sidebar__utility:focus-visible {
            color: #11bec8;
            text-decoration: none;
            outline: 0;
        }

        .dashboard-sidebar__logout {
            color: #c52121;
        }

        .dashboard-sidebar__logout:hover,
        .dashboard-sidebar__logout:focus-visible {
            color: #9e1616;
            outline: 0;
        }

        @media (max-width: 860px) {
            .dashboard-sidebar {
                width: 100%;
                min-height: auto;
                border-right: 0;
                border-bottom: 1px solid #d7dce7;
                padding: 22px 18px;
            }

            .dashboard-sidebar__nav {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                margin-top: 24px;
            }

            .dashboard-sidebar__bottom {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                margin-top: 24px;
            }
        }

        @media (max-width: 520px) {
            .dashboard-sidebar__nav,
            .dashboard-sidebar__bottom {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endonce

<aside class="dashboard-sidebar" aria-label="Navigasi dashboard">
    <div class="dashboard-sidebar__brand">
        <img class="dashboard-sidebar__logo" src="{{ asset('images/kresek-wordmark.svg') }}" alt="Kresek.in">
        <div class="dashboard-sidebar__subtitle">Management Portal</div>
    </div>

    <nav class="dashboard-sidebar__nav" aria-label="Menu utama">
        @foreach ($items as $item)
            <a
                class="dashboard-sidebar__item @if ($active === $item['key']) is-active @endif"
                href="{{ $item['href'] }}"
                @if ($active === $item['key']) aria-current="page" @endif
            >
                @include('components.dashboard.sidebar-icon', ['name' => $item['icon'], 'class' => 'dashboard-sidebar__icon'])
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="dashboard-sidebar__bottom">
        <a class="dashboard-sidebar__utility" href="mailto:admin@kresek.in?subject=Bantuan%20Portal%20Kresek.in">
            @include('components.dashboard.sidebar-icon', ['name' => 'support', 'class' => 'dashboard-sidebar__icon'])
            <span>Support</span>
        </a>

        <button class="dashboard-sidebar__logout" type="button" data-dashboard-logout>
            @include('components.dashboard.sidebar-icon', ['name' => 'logout', 'class' => 'dashboard-sidebar__icon'])
            <span>Logout</span>
        </button>
    </div>
</aside>

@once
    <script>
        document.addEventListener('click', async (event) => {
            const logoutButton = event.target.closest('[data-dashboard-logout]');

            if (!logoutButton) {
                return;
            }

            const token = localStorage.getItem('kresekin_token');
            logoutButton.disabled = true;

            try {
                if (token) {
                    await fetch('{{ url('/api/users/logout') }}', {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            Authorization: `Bearer ${token}`,
                        },
                    });
                }
            } finally {
                localStorage.removeItem('kresekin_token');
                localStorage.removeItem('kresekin_token_type');
                localStorage.removeItem('kresekin_user_role');
                window.location.assign('{{ url('/') }}');
            }
        });
    </script>
@endonce
