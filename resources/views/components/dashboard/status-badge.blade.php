@props([
    'status' => 'success',
    'label' => null,
])

@php
    $text = $label ?? match ($status) {
        'success', 'approved' => 'Success',
        'pending', 'processing', 'estimated' => 'Pending',
        'failed', 'rejected' => 'Failed',
        default => ucfirst($status),
    };
@endphp

@once
    <style>
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border-radius: 999px;
            padding: 7px 13px;
            font-size: 15px;
            font-weight: 900;
            line-height: 1;
            white-space: nowrap;
        }

        .status-badge::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-badge--success,
        .status-badge--approved {
            color: #075f3d;
            background: #e7f2ee;
        }

        .status-badge--pending,
        .status-badge--processing,
        .status-badge--estimated {
            color: #0b53a4;
            background: #e8f0ff;
        }

        .status-badge--failed,
        .status-badge--rejected {
            color: #c52121;
            background: #ffecec;
        }
    </style>
@endonce

<span class="status-badge status-badge--{{ $status }}">{{ $text }}</span>
