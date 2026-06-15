@props([
    'title',
    'value',
    'icon' => 'money',
    'growth' => null,
    'caption' => null,
    'tone' => 'cyan',
])

@once
    <style>
        .metric-card {
            min-height: 170px;
            display: grid;
            align-content: space-between;
            gap: 20px;
            border: 1px solid #e3e8f0;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 14px 34px rgba(16, 24, 40, .07);
            padding: 28px 30px;
        }

        .metric-card__top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
        }

        .metric-card__growth,
        .metric-card__caption {
            color: #075f3d;
            font-size: 16px;
            font-weight: 900;
            letter-spacing: .02em;
            white-space: nowrap;
        }

        .metric-card__caption {
            color: #343846;
        }

        .metric-card__label {
            margin: 0 0 10px;
            color: #52617f;
            font-size: 16px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .metric-card__value {
            color: #171b23;
            font-size: clamp(32px, 3.2vw, 42px);
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: 0;
        }
    </style>
@endonce

<article {{ $attributes->merge(['class' => 'metric-card']) }}>
    <div class="metric-card__top">
        <x-dashboard.icon-tile :icon="$icon" :tone="$tone" />

        @if ($growth !== null)
            <div class="metric-card__growth">↗ +{{ $growth }}%</div>
        @elseif ($caption)
            <div class="metric-card__caption">{{ $caption }}</div>
        @endif
    </div>

    <div>
        <p class="metric-card__label">{{ $title }}</p>
        <div class="metric-card__value">{{ $value }}</div>
    </div>
</article>
