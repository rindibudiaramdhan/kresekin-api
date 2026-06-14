@props([
    'title',
    'columns' => [],
    'rows' => [],
    'actionUrl' => '#',
    'emptyMessage' => 'Belum ada data.',
])

@once
    <style>
        .data-table-card {
            overflow: hidden;
            border: 1px solid #e3e8f0;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 14px 34px rgba(16, 24, 40, .07);
        }

        .data-table-card__header {
            min-height: 62px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 1px solid #e7ebf2;
            padding: 0 28px;
        }

        .data-table-card__title {
            margin: 0;
            color: #171b23;
            font-size: 26px;
            font-weight: 900;
            letter-spacing: 0;
        }

        .data-table-card__action {
            color: #11bec8;
            font-size: 24px;
            font-weight: 900;
            text-decoration: none;
        }

        .data-table-card__scroll {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            min-width: 760px;
            border-collapse: collapse;
        }

        .data-table th {
            height: 58px;
            color: #52617f;
            background: #f6f7f9;
            padding: 0 24px;
            text-align: left;
            font-size: 15px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .data-table td {
            height: 70px;
            border-top: 1px solid #edf0f5;
            padding: 0 24px;
            color: #171b23;
            font-size: 16px;
            font-weight: 800;
            vertical-align: middle;
        }

        .data-table__muted {
            color: #52617f;
            font-weight: 700;
        }

        .data-table__entity {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        .data-table__empty {
            padding: 28px;
            color: #52617f;
            font-weight: 800;
        }
    </style>
@endonce

<section class="data-table-card">
    <div class="data-table-card__header">
        <h2 class="data-table-card__title">{{ $title }}</h2>
        <a class="data-table-card__action" href="{{ $actionUrl }}" aria-label="Lihat detail {{ $title }}">›</a>
    </div>

    @if (count($rows) > 0)
        <div class="data-table-card__scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            <th>{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            @foreach ($columns as $column)
                                <td>{{ $row[$column['key']] ?? '' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="data-table__empty">{{ $emptyMessage }}</div>
    @endif
</section>
