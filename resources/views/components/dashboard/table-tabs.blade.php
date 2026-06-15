@props([
    'tabs' => [],
    'active' => null,
])

@once
    <style>
        .table-tabs {
            display: flex;
            align-items: flex-end;
            gap: 30px;
            min-height: 74px;
            border-bottom: 1px solid #d9dee9;
            padding: 0 28px;
        }

        .table-tabs__item {
            position: relative;
            min-height: 74px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: 0;
            background: transparent;
            color: #555c69;
            cursor: pointer;
            font: inherit;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 0;
            padding: 0;
        }

        .table-tabs__item.is-active {
            color: #11bec8;
        }

        .table-tabs__item.is-active::after {
            content: "";
            position: absolute;
            right: 0;
            bottom: 0;
            left: 0;
            height: 2px;
            background: currentColor;
        }

        .table-tabs__icon {
            width: 22px;
            height: 22px;
            flex: 0 0 auto;
        }
    </style>
@endonce

<div {{ $attributes->class(['table-tabs']) }} role="tablist">
    @foreach ($tabs as $tab)
        @php($isActive = ($active ?? array_key_first($tabs)) === $tab['key'])
        <button class="table-tabs__item @if ($isActive) is-active @endif" type="button" role="tab" aria-selected="{{ $isActive ? 'true' : 'false' }}">
            <span class="table-tabs__icon" aria-hidden="true">
                @if (($tab['icon'] ?? '') === 'agent')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 3h8v4H8zM6 7h12v13H6z"/>
                        <path d="M9 11h6M9 15h3"/>
                    </svg>
                @else
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 10h16l-1-5H5l-1 5Z"/>
                        <path d="M6 10v9h12v-9"/>
                        <path d="M8 10v3M12 10v3M16 10v3"/>
                    </svg>
                @endif
            </span>
            <span>{{ $tab['label'] }}</span>
        </button>
    @endforeach
</div>
