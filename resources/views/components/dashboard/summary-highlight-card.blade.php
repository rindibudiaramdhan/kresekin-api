@props([
    'label',
    'value',
    'footerLabel',
    'footerValue',
])

@once
    <style>
        .summary-highlight-card {
            position: relative;
            overflow: hidden;
            min-height: 252px;
            display: grid;
            align-content: center;
            border-radius: 12px;
            background: #0ca2a7;
            box-shadow: 0 14px 34px rgba(16, 24, 40, .12);
            padding: 34px;
            color: #ffffff;
        }

        .summary-highlight-card::after {
            content: "";
            position: absolute;
            right: 28px;
            top: 28px;
            width: 130px;
            height: 130px;
            border: 12px solid rgba(255, 255, 255, .09);
            border-radius: 50%;
        }

        .summary-highlight-card__icon {
            width: 44px;
            height: 36px;
            display: grid;
            place-items: center;
            border-radius: 8px;
            color: #ffffff;
            background: rgba(255, 255, 255, .18);
            margin-bottom: 22px;
        }

        .summary-highlight-card__label {
            max-width: 260px;
            color: rgba(255, 255, 255, .86);
            font-size: 21px;
            line-height: 1.35;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .summary-highlight-card__value {
            margin-top: 10px;
            font-size: clamp(34px, 3vw, 42px);
            font-weight: 900;
            line-height: 1.05;
        }

        .summary-highlight-card__footer {
            margin-top: 26px;
            color: rgba(255, 255, 255, .9);
            font-size: 20px;
        }

        .summary-highlight-card__footer strong {
            color: #ffffff;
            margin-left: 8px;
        }
    </style>
@endonce

<section {{ $attributes->merge(['class' => 'summary-highlight-card']) }}>
    <div class="summary-highlight-card__icon" aria-hidden="true">
        <svg width="27" height="27" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="6" width="18" height="12" rx="2"/>
            <circle cx="12" cy="12" r="3"/>
            <path d="M6 9h2M16 15h2"/>
        </svg>
    </div>
    <div class="summary-highlight-card__label">{{ $label }}</div>
    <div class="summary-highlight-card__value">{{ $value }}</div>
    <div class="summary-highlight-card__footer">{{ $footerLabel }}: <strong>{{ $footerValue }}</strong></div>
</section>
