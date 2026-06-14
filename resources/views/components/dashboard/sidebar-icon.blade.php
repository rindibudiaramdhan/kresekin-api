@props([
    'name',
    'class' => '',
])

@switch($name)
    @case('dashboard')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <rect x="4" y="4" width="6" height="6"/>
            <rect x="14" y="4" width="6" height="6"/>
            <rect x="4" y="14" width="6" height="6"/>
            <rect x="14" y="14" width="6" height="6"/>
        </svg>
        @break

    @case('umkm')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <rect x="5" y="4" width="14" height="17" rx="2"/>
            <path d="M9 4v3h6V4"/>
            <circle cx="12" cy="12" r="3"/>
            <path d="M8 18c.8-2 2.1-3 4-3s3.2 1 4 3"/>
        </svg>
        @break

    @case('finance')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <rect x="3" y="7" width="18" height="12" rx="1.5"/>
            <path d="M7 7V5h10v2"/>
            <circle cx="12" cy="13" r="2.2"/>
            <path d="M6 10h2M16 16h2"/>
        </svg>
        @break

    @case('settings')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/>
            <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1A2 2 0 1 1 4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.6-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1A2 2 0 1 1 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.6V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1A2 2 0 1 1 19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.1a2 2 0 1 1 0 4H21a1.7 1.7 0 0 0-1.6 1Z"/>
        </svg>
        @break

    @case('support')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <path d="M4 13a8 8 0 0 1 16 0"/>
            <path d="M4 13v4a2 2 0 0 0 2 2h2v-6H6a2 2 0 0 0-2 2"/>
            <path d="M20 13v4a2 2 0 0 1-2 2h-2v-6h2a2 2 0 0 1 2 2"/>
            <path d="M9 10h.01M15 10h.01"/>
        </svg>
        @break

    @case('logout')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <path d="M10 17H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/>
            <path d="M15 7l5 5-5 5"/>
            <path d="M20 12H9"/>
        </svg>
        @break
@endswitch
