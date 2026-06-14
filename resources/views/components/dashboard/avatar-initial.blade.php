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
            font-weight: 900;
        }

        .avatar-initial--cyan {
            color: #11bec8;
            background: #ffffff;
        }

        .avatar-initial--soft {
            color: #52617f;
            background: #f1f5fb;
        }

        .avatar-initial--red {
            color: #c52121;
            background: #fff2f2;
        }
    </style>
@endonce

<span class="avatar-initial avatar-initial--{{ $tone }}">{{ $initials }}</span>
