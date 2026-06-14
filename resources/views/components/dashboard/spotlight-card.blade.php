@props([
    'title',
    'subtitle',
    'items' => [],
    'actionLabel' => 'Lihat Detail',
    'actionUrl' => '#',
])

@once
    <style>
        .spotlight-card {
            min-height: 430px;
            display: flex;
            flex-direction: column;
            border-radius: 12px;
            background: #0ca2a7;
            box-shadow: 0 14px 34px rgba(16, 24, 40, .12);
            padding: 30px;
            color: #ffffff;
        }

        .spotlight-card__title {
            margin: 0;
            font-size: 28px;
            font-weight: 900;
        }

        .spotlight-card__subtitle {
            margin: 14px 0 0;
            color: rgba(255, 255, 255, .82);
            font-size: 18px;
        }

        .spotlight-card__list {
            display: grid;
            gap: 22px;
            margin-top: 34px;
        }

        .spotlight-card__item {
            min-height: 82px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 16px;
            border-radius: 9px;
            background: rgba(255, 255, 255, .1);
            padding: 14px 16px;
        }

        .spotlight-card__name {
            color: #ffffff;
            font-size: 16px;
            font-weight: 900;
            letter-spacing: .04em;
        }

        .spotlight-card__category {
            margin-top: 4px;
            color: rgba(255, 255, 255, .72);
            font-size: 13px;
            font-weight: 700;
        }

        .spotlight-card__growth {
            color: #69f090;
            font-size: 16px;
            font-weight: 900;
        }

        .spotlight-card__action {
            min-height: 54px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: auto;
            border: 1px solid rgba(255, 255, 255, .3);
            border-radius: 8px;
            color: #ffffff;
            text-decoration: none;
            font-size: 17px;
            font-weight: 900;
        }
    </style>
@endonce

<section class="spotlight-card">
    <h2 class="spotlight-card__title">{{ $title }}</h2>
    <p class="spotlight-card__subtitle">{{ $subtitle }}</p>

    <div class="spotlight-card__list">
        @foreach ($items as $item)
            <div class="spotlight-card__item">
                <x-dashboard.avatar-initial :initials="$item['initials']" tone="cyan" />
                <div>
                    <div class="spotlight-card__name">{{ $item['name'] }}</div>
                    <div class="spotlight-card__category">{{ $item['category'] }}</div>
                </div>
                <div class="spotlight-card__growth">+{{ $item['growth'] }}%</div>
            </div>
        @endforeach
    </div>

    <a class="spotlight-card__action" href="{{ $actionUrl }}">{{ $actionLabel }}</a>
</section>
