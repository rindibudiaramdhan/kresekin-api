@props([
    'title',
    'activePeriod' => '30 Days',
])

@once
    <style>
        .trend-card {
            min-height: 430px;
            border: 1px solid #e3e8f0;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 14px 34px rgba(16, 24, 40, .07);
            padding: 28px 30px;
        }

        .trend-card__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
        }

        .trend-card__title {
            margin: 0;
            color: #171b23;
            font-size: 26px;
            font-weight: 900;
        }

        .trend-card__chart {
            width: 100%;
            height: auto;
            display: block;
            margin-top: 34px;
        }

        .trend-card__axis {
            fill: #7d8494;
            font-size: 12px;
            font-weight: 800;
        }
    </style>
@endonce

<section {{ $attributes->merge(['class' => 'trend-card']) }}>
    <div class="trend-card__header">
        <h2 class="trend-card__title">{{ $title }}</h2>
        <x-dashboard.period-toggle :active="$activePeriod" />
    </div>

    <svg class="trend-card__chart" viewBox="0 0 760 330" role="img" aria-label="{{ $title }}">
        <defs>
            <linearGradient id="trendFill" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#b8d2f5" stop-opacity=".86"/>
                <stop offset="100%" stop-color="#eef5ff" stop-opacity=".18"/>
            </linearGradient>
        </defs>
        <path d="M22 278 H730 M22 204 H730 M22 130 H730 M22 56 H730" stroke="#e1e5eb" stroke-width="1"/>
        <path class="trend-card__area" d="M22 278 H752 L752 278 L22 278 Z" fill="url(#trendFill)"/>
        <path class="trend-card__line" d="M22 278 H752" fill="none" stroke="#11bec8" stroke-width="4" stroke-linecap="round"/>
        <text class="trend-card__axis" x="22" y="318"></text>
        <text class="trend-card__axis" x="160" y="318"></text>
        <text class="trend-card__axis" x="300" y="318"></text>
        <text class="trend-card__axis" x="438" y="318"></text>
        <text class="trend-card__axis" x="578" y="318"></text>
        <text class="trend-card__axis" x="705" y="318"></text>
    </svg>
</section>
