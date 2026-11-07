@props([
    'status' => 'success',
    'label' => null,
])

@php
    $text = $label ?? match ($status) {
        'success', 'approved' => 'Success',
        'pending', 'estimated' => 'Pending',
        'processing', 'warning' => 'Diproses',
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
            border-radius: var(--ds-radius-pill);
            padding: 7px 13px;
            font-size: 15px;
            font-weight: 700;
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
            color: var(--ds-success);
            background: var(--ds-success-soft);
        }

        .status-badge--pending,
        .status-badge--estimated {
            color: var(--ds-pending);
            background: var(--ds-pending-soft);
        }

        .status-badge--processing,
        .status-badge--warning {
            color: var(--ds-processing);
            background: var(--ds-processing-soft);
        }

        .status-badge--failed,
        .status-badge--rejected {
            color: var(--ds-danger);
            background: var(--ds-danger-soft);
        }
    </style>
@endonce

<span class="status-badge status-badge--{{ $status }}">{{ $text }}</span>
