@props([
    'approveLabel' => 'Disetujui',
    'rejectLabel' => 'Ditolak',
])

@once
    <style>
        .approval-actions {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            white-space: nowrap;
        }

        .approval-actions__button {
            width: 42px;
            height: 42px;
            display: inline-grid;
            place-items: center;
            border: 3px solid currentColor;
            border-radius: 50%;
            background: #ffffff;
            cursor: pointer;
            transition: background .16s ease, transform .16s ease;
        }

        .approval-actions__button:hover,
        .approval-actions__button:focus-visible {
            background: #f8fafc;
            outline: 0;
            transform: translateY(-1px);
        }

        .approval-actions__button--approve {
            color: #2fa45a;
        }

        .approval-actions__button--reject {
            color: #c52121;
        }

        .approval-actions__icon {
            width: 28px;
            height: 28px;
        }
    </style>
@endonce

<div {{ $attributes->class(['approval-actions']) }}>
    <button class="approval-actions__button approval-actions__button--approve" type="button" data-finance-decision="approve" aria-label="{{ $approveLabel }}" title="{{ $approveLabel }}">
        <svg class="approval-actions__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m5 12 4.5 4.5L19 7"/>
        </svg>
    </button>

    <button class="approval-actions__button approval-actions__button--reject" type="button" data-finance-decision="reject" aria-label="{{ $rejectLabel }}" title="{{ $rejectLabel }}">
        <svg class="approval-actions__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M6 6l12 12M18 6 6 18"/>
        </svg>
    </button>
</div>
