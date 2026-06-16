@props([
    'type' => 'text',
    'icon' => 'search',
    'placeholder' => '',
    'value' => '',
    'options' => [],
    'label' => null,
])

@php
    $hasIcon = filled($icon);
@endphp

@once
    <style>
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
        }

        .filter-field {
            position: relative;
            min-width: 0;
        }

        .filter-field__control {
            width: 100%;
            height: 48px;
            border: 1px solid #c7cede;
            border-radius: 8px;
            background: #ffffff;
            color: #52617f;
            font: inherit;
            font-size: 17px;
            font-weight: 700;
            letter-spacing: 0;
            outline: 0;
            padding: 0 46px;
            appearance: none;
        }

        .filter-field--plain .filter-field__control {
            padding-left: 22px;
        }

        .filter-field__control::placeholder {
            color: #a1a7b4;
            opacity: 1;
        }

        .filter-field__control:focus {
            border-color: #11bec8;
            box-shadow: 0 0 0 3px rgba(17, 190, 200, .14);
        }

        .filter-field__date-range {
            display: flex;
            align-items: center;
            gap: 6px;
            overflow: hidden;
        }

        .filter-field__date-input {
            min-width: 0;
            width: 100%;
            border: 0;
            background: transparent;
            color: inherit;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0;
            outline: 0;
            padding: 0;
        }

        .filter-field__date-input::-webkit-calendar-picker-indicator {
            cursor: pointer;
            opacity: 0;
            position: absolute;
        }

        .filter-field__date-separator {
            color: #8a94a8;
            flex: 0 0 auto;
            font-size: 15px;
            font-weight: 800;
        }

        .filter-field__icon,
        .filter-field__chevron {
            position: absolute;
            top: 50%;
            width: 22px;
            height: 22px;
            color: #6b7487;
            pointer-events: none;
            transform: translateY(-50%);
        }

        .filter-field__icon {
            left: 16px;
        }

        .filter-field__chevron {
            right: 16px;
        }
    </style>
@endonce

<label {{ $attributes->class(['filter-field', 'filter-field--plain' => ! $hasIcon]) }}>
    @if ($label)
        <span class="sr-only">{{ $label }}</span>
    @endif

    @if ($hasIcon)
        <span class="filter-field__icon" aria-hidden="true">
            @switch($icon)
                @case('calendar')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 3v4M16 3v4M4 9h16"/>
                        <rect x="4" y="5" width="16" height="16" rx="2"/>
                    </svg>
                    @break

                @case('users')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 20c0-2.2-1.8-4-4-4H7c-2.2 0-4 1.8-4 4"/>
                        <circle cx="9.5" cy="8" r="3"/>
                        <path d="M17 11a3 3 0 1 0-1.4-5.7"/>
                        <path d="M21 20c0-1.8-1.1-3.3-2.7-3.8"/>
                    </svg>
                    @break

                @default
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m20 20-3.4-3.4"/>
                    </svg>
            @endswitch
        </span>
    @endif

    @if ($type === 'select')
        <select class="filter-field__control" aria-label="{{ $label ?? $placeholder }}">
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
            @endforeach
        </select>
        <span class="filter-field__chevron" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                <path d="m7 10 5 5 5-5"/>
            </svg>
        </span>
    @elseif ($type === 'date-range')
        <span class="filter-field__control filter-field__date-range" role="group" aria-label="{{ $label ?? $placeholder }}">
            <input class="filter-field__date-input" type="date" aria-label="Tanggal mulai" data-date-from>
            <span class="filter-field__date-separator" aria-hidden="true">-</span>
            <input class="filter-field__date-input" type="date" aria-label="Tanggal akhir" data-date-to>
        </span>
    @else
        <input class="filter-field__control" type="text" value="{{ $value }}" placeholder="{{ $placeholder }}" aria-label="{{ $label ?? $placeholder }}">
    @endif
</label>
