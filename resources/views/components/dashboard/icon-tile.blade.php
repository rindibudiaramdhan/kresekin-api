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

        .icon-tile__icon {
            width: 26px;
            height: 26px;
        }
    </style>
@endonce

<span class="icon-tile @if ($tone === 'green') icon-tile--green @endif" aria-hidden="true">
    @switch($icon)
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
