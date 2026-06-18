@props([
    'title' => 'Admin Views',
    'panelLabel' => null,
    'userName' => 'System Administrator',
])

@once
    <style>
        .dashboard-header {
            position: sticky;
            top: 0;
            z-index: 20;
            min-height: 82px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            border-bottom: 1px solid #e1e5ee;
            background: rgba(248, 250, 252, .96);
            backdrop-filter: blur(14px);
            padding: 0 34px;
        }

        .dashboard-header__left {
            display: flex;
            align-items: center;
            gap: 28px;
            min-width: 0;
        }

        .dashboard-header__title {
            margin: 0;
            color: #11bec8;
            font-size: 25px;
            font-weight: 900;
            letter-spacing: 0;
            white-space: nowrap;
        }

        .dashboard-header__divider {
            width: 2px;
            height: 32px;
            background: #d5dbe7;
        }

        .dashboard-header__panel-label {
            color: #5c667a;
            font-size: 16px;
            font-weight: 900;
            white-space: nowrap;
        }

        .dashboard-header__right {
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .dashboard-header__notification {
            width: 42px;
            height: 42px;
            display: inline-grid;
            place-items: center;
            border: 0;
            border-radius: 12px;
            color: #3f4656;
            background: transparent;
            cursor: pointer;
        }

        .dashboard-header__notification:hover,
        .dashboard-header__notification:focus-visible {
            color: #11bec8;
            background: #edf7f9;
            outline: 0;
        }

        .dashboard-header__icon {
            width: 24px;
            height: 24px;
            flex: 0 0 auto;
        }

        .dashboard-header__user {
            min-height: 50px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            border-radius: 999px;
            background: #e8eaee;
            padding: 7px 20px 7px 8px;
            color: #242833;
            font-size: 16px;
            font-weight: 900;
            letter-spacing: .01em;
            white-space: nowrap;
        }

        .dashboard-header__avatar {
            width: 36px;
            height: 36px;
            display: inline-grid;
            place-items: center;
            border-radius: 50%;
            color: #ffffff;
            background: #11bec8;
        }

        .dashboard-header__avatar svg {
            width: 20px;
            height: 20px;
        }

        @media (max-width: 700px) {
            .dashboard-header {
                min-height: auto;
                align-items: flex-start;
                flex-direction: column;
                padding: 18px;
            }

            .dashboard-header__right {
                width: 100%;
                justify-content: space-between;
            }

            .dashboard-header__user {
                max-width: calc(100vw - 96px);
                overflow: hidden;
            }

            .dashboard-header__user-name {
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }
    </style>
@endonce

<header class="dashboard-header">
    <div class="dashboard-header__left">
        <h1 class="dashboard-header__title">{{ $title }}</h1>
        <span class="dashboard-header__divider" aria-hidden="true"></span>
        @if ($panelLabel)
            <span class="dashboard-header__panel-label">{{ $panelLabel }}</span>
        @endif
    </div>

    <div class="dashboard-header__right">
        <button class="dashboard-header__notification" type="button" aria-label="Notifikasi">
            <svg class="dashboard-header__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                <path d="M10 21h4"/>
            </svg>
        </button>

        <div class="dashboard-header__user" aria-label="Pengguna aktif">
            <span class="dashboard-header__avatar" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor" focusable="false">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4.5 20a7.5 7.5 0 0 1 15 0"/>
                </svg>
            </span>
            <span class="dashboard-header__user-name">{{ $userName }}</span>
        </div>
    </div>
</header>
