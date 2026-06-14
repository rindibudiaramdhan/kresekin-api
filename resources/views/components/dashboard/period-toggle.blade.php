@props([
    'options' => ['30 Days', '90 Days'],
    'active' => '30 Days',
])

@once
    <style>
        .period-toggle {
            display: inline-flex;
            align-items: center;
            gap: 14px;
        }

        .period-toggle__item {
            min-height: 34px;
            display: inline-flex;
            align-items: center;
            border-radius: 8px;
            color: #777d8c;
            padding: 0 16px;
            font-size: 15px;
            font-weight: 900;
            text-decoration: none;
        }

        .period-toggle__item.is-active {
            color: #547477;
            background: #eef0f2;
        }
    </style>
@endonce

<div class="period-toggle" aria-label="Filter periode">
    @foreach ($options as $option)
        <a class="period-toggle__item @if ($option === $active) is-active @endif" href="#">{{ $option }}</a>
    @endforeach
</div>
