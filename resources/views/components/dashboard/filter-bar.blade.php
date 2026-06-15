@props([
    'buttonLabel' => 'Terapkan Filter',
])

@once
    <style>
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 24px;
            border: 1px solid #e3e8f0;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 14px 34px rgba(16, 24, 40, .07);
            padding: 32px 24px;
        }

        .filter-bar__fields {
            display: grid;
            grid-template-columns: minmax(260px, 430px) minmax(180px, 220px) minmax(220px, 260px);
            gap: 24px;
            flex: 1 1 auto;
        }

        .filter-bar__button {
            height: 48px;
            border: 0;
            border-radius: 8px;
            background: #11bec8;
            color: #ffffff;
            cursor: pointer;
            font: inherit;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 0;
            padding: 0 28px;
            white-space: nowrap;
        }

        .filter-bar__button:hover,
        .filter-bar__button:focus-visible {
            background: #0aaab3;
            outline: 0;
        }

        @media (max-width: 1120px) {
            .filter-bar__fields {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 680px) {
            .filter-bar {
                padding: 22px;
            }

            .filter-bar__fields {
                grid-template-columns: 1fr;
                width: 100%;
            }

            .filter-bar__button {
                width: 100%;
            }
        }
    </style>
@endonce

<form {{ $attributes->class(['filter-bar']) }} action="#" method="get">
    <div class="filter-bar__fields">
        {{ $slot }}
    </div>

    <button class="filter-bar__button" type="button">{{ $buttonLabel }}</button>
</form>
