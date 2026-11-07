@props([
    'initials',
    'tone' => 'blue',
])

@once
    <style>
        .avatar-initial {
            width: 30px;
            height: 30px;
            display: inline-grid;
            place-items: center;
            border-radius: 7px;
            color: #174b8f;
            background: #dbe9ff;
            font-size: 12px;
            font-weight: 700;
        }

        .avatar-initial--cyan {
            color: var(--ds-brand);
            background: var(--ds-surface);
        }

        .avatar-initial--soft {
            color: var(--ds-text-muted);
            background: #f1f5fb;
        }

        .avatar-initial--red {
            color: var(--ds-danger);
            background: #fff2f2;
        }
    </style>
@endonce

<span class="avatar-initial avatar-initial--{{ $tone }}">{{ $initials }}</span>
