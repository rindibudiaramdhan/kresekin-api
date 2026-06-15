@props([
    'summary' => '',
    'pages' => [1],
    'current' => 1,
])

@once
    <style>
        .dashboard-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            border-top: 1px solid #d9dee9;
            padding: 28px 34px;
        }

        .dashboard-pagination__summary {
            color: #4f586b;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0;
        }

        .dashboard-pagination__controls {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        .dashboard-pagination__button,
        .dashboard-pagination__page {
            width: 46px;
            height: 46px;
            display: inline-grid;
            place-items: center;
            border: 1px solid #c7cede;
            border-radius: 6px;
            background: #ffffff;
            color: #151922;
            font: inherit;
            font-size: 16px;
            font-weight: 900;
            text-decoration: none;
        }

        .dashboard-pagination__button {
            color: #4f586b;
        }

        .dashboard-pagination__button[aria-disabled="true"] {
            color: #9ca3af;
            background: #f8fafc;
        }

        .dashboard-pagination__page.is-current {
            border-color: #11bec8;
            background: #11bec8;
            color: #ffffff;
        }

        .dashboard-pagination__ellipsis {
            color: #6b7487;
            font-weight: 900;
            padding: 0 4px;
        }

        .dashboard-pagination__icon {
            width: 22px;
            height: 22px;
        }

        @media (max-width: 760px) {
            .dashboard-pagination {
                align-items: flex-start;
                flex-direction: column;
                padding: 24px;
            }

            .dashboard-pagination__controls {
                flex-wrap: wrap;
            }
        }
    </style>
@endonce

<nav {{ $attributes->class(['dashboard-pagination']) }} aria-label="Pagination">
    <div class="dashboard-pagination__summary">{{ $summary }}</div>

    <div class="dashboard-pagination__controls">
        <a class="dashboard-pagination__button" href="#" aria-disabled="true" aria-label="Halaman sebelumnya">
            <svg class="dashboard-pagination__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
            </svg>
        </a>

        @foreach ($pages as $page)
            @if ($page === '...')
                <span class="dashboard-pagination__ellipsis">...</span>
            @else
                <a class="dashboard-pagination__page @if ((int) $page === (int) $current) is-current @endif" href="#" @if ((int) $page === (int) $current) aria-current="page" @endif>{{ $page }}</a>
            @endif
        @endforeach

        <a class="dashboard-pagination__button" href="#" aria-label="Halaman berikutnya">
            <svg class="dashboard-pagination__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                <path d="m9 18 6-6-6-6"/>
            </svg>
        </a>
    </div>
</nav>
