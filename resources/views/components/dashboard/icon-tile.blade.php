@props([
    'icon' => 'money',
    'tone' => 'cyan',
])

@once
    <style>
        .icon-tile {
            width: 48px;
            height: 48px;
            display: inline-grid;
            place-items: center;
            border-radius: 10px;
            color: #11bec8;
            background: #edf4ff;
        }

        .icon-tile--green {
            color: #075f3d;
            background: #e7f2ee;
        }

        .icon-tile--blue {
            color: #174b8f;
            background: #e8f0ff;
        }

        .icon-tile--yellow {
            color: #d97706;
            background: #fff4cc;
        }

        .icon-tile__icon {
            width: 26px;
            height: 26px;
        }
    </style>
@endonce

<span class="icon-tile @if (in_array($tone, ['green', 'blue', 'yellow'], true)) icon-tile--{{ $tone }} @endif" aria-hidden="true">
    @switch($icon)
        @case('check')
            <svg class="icon-tile__icon" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="12" r="9"/>
                <path d="m8.2 12.1 2.2 2.2 5-5" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            @break

        @case('wallet')
            <svg class="icon-tile__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5H18a2 2 0 0 1 2 2v2.5"/>
                <path d="M4 7.5v9A2.5 2.5 0 0 0 6.5 19H20a1 1 0 0 0 1-1v-7.5a1 1 0 0 0-1-1H7"/>
                <path d="M17 14h.01"/>
            </svg>
            @break

        @case('clipboard-clock')
            <svg class="icon-tile__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 4h6l1 2h2a2 2 0 0 1 2 2v5.2"/>
                <path d="M8 6H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h6.2"/>
                <path d="M9 4h6v4H9z"/>
                <circle cx="17" cy="17" r="4"/>
                <path d="M17 15v2l1.4 1"/>
            </svg>
            @break

        @case('cart')
            <svg class="icon-tile__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 5h2l2.2 10.2A2 2 0 0 0 10.2 17H18a2 2 0 0 0 1.9-1.4L22 8H7"/>
                <circle cx="10" cy="21" r="1"/>
                <circle cx="18" cy="21" r="1"/>
            </svg>
            @break

        @case('store')
            <svg class="icon-tile__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 10h16l-1-5H5l-1 5Z"/>
                <path d="M6 10v9h12v-9"/>
                <path d="M8 10v3M12 10v3M16 10v3"/>
            </svg>
            @break

        @default
            <svg class="icon-tile__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="6" width="18" height="12" rx="2"/>
                <circle cx="12" cy="12" r="3"/>
                <path d="M6 9h2M16 15h2"/>
            </svg>
    @endswitch
</span>
