@props([
    'title' => 'Approve Pencairan Dana',
    'description' => 'Anda akan menyetujui pencairan dana agent berikut',
    'note' => 'Dana akan diproses ke rekening tujuan dan status tidak dapat dibatalkan setelah dikonfirmasi',
    'confirmLabel' => 'Ya, Approve',
    'name' => 'approval',
    'actorLabel' => 'Nama Agent',
])

@once
    <style>
        .approval-modal {
            position: fixed;
            inset: 0;
            z-index: 80;
            display: grid;
            place-items: center;
            background: rgba(15, 23, 42, .42);
            padding: 20px;
        }

        .approval-modal[hidden] {
            display: none;
        }

        .approval-modal__panel {
            width: min(760px, 100%);
            border-radius: 22px;
            background: #ffffff;
            color: #0b0b0f;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .24);
            padding: 38px 40px 46px;
        }

        .approval-modal__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            border-bottom: 1px solid #c5c7cc;
            padding-bottom: 18px;
        }

        .approval-modal__title {
            margin: 0;
            font-size: clamp(32px, 4vw, 46px);
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: 0;
        }

        .approval-modal__close {
            width: 48px;
            height: 48px;
            display: inline-grid;
            place-items: center;
            border: 0;
            background: transparent;
            color: #0b0b0f;
            cursor: pointer;
            padding: 0;
        }

        .approval-modal__close:hover,
        .approval-modal__close:focus-visible {
            color: #11bec8;
            outline: 0;
        }

        .approval-modal__close svg {
            width: 40px;
            height: 40px;
        }

        .approval-modal__description {
            margin: 22px 0 28px;
            color: #5c5c5f;
            font-size: 28px;
            font-weight: 500;
            line-height: 1.25;
            letter-spacing: 0;
        }

        .approval-modal__details {
            display: grid;
            grid-template-columns: max-content 1fr;
            gap: 18px 20px;
            color: #5c5c5f;
            font-size: 28px;
            font-weight: 900;
            line-height: 1.2;
        }

        .approval-modal__details dt,
        .approval-modal__details dd {
            margin: 0;
        }

        .approval-modal__label {
            min-width: 176px;
        }

        .approval-modal__value::before {
            content: ": ";
        }

        .approval-modal__note {
            margin: 40px 0 34px;
            color: #5c5c5f;
            font-size: 27px;
            font-weight: 500;
            line-height: 1.12;
            letter-spacing: 0;
        }

        .approval-modal__actions {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .approval-modal__button {
            width: min(290px, 100%);
            min-height: 88px;
            border: 0;
            border-radius: 12px;
            color: #ffffff;
            cursor: pointer;
            font: inherit;
            font-size: 28px;
            font-weight: 900;
            letter-spacing: 0;
        }

        .approval-modal__button--cancel {
            background: #c6c6c9;
        }

        .approval-modal__button--confirm {
            background: #11bec8;
        }

        .approval-modal__button:hover,
        .approval-modal__button:focus-visible {
            filter: brightness(.96);
            outline: 0;
        }

        @media (max-width: 680px) {
            .approval-modal__panel {
                padding: 28px 22px 30px;
            }

            .approval-modal__description,
            .approval-modal__details,
            .approval-modal__note,
            .approval-modal__button {
                font-size: 20px;
            }

            .approval-modal__details {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            .approval-modal__value::before {
                content: "";
            }

            .approval-modal__actions {
                flex-direction: column;
            }

            .approval-modal__button {
                width: 100%;
                min-height: 62px;
            }
        }
    </style>
@endonce

<div {{ $attributes->class(['approval-modal']) }} hidden data-finance-modal="{{ $name }}" role="dialog" aria-modal="true" aria-labelledby="{{ $name }}-modal-title">
    <div class="approval-modal__panel">
        <div class="approval-modal__header">
            <h2 class="approval-modal__title" id="{{ $name }}-modal-title">{{ $title }}</h2>
            <button class="approval-modal__close" type="button" data-finance-modal-close aria-label="Tutup modal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <p class="approval-modal__description">{{ $description }}</p>

        <dl class="approval-modal__details">
            <dt class="approval-modal__label">ID Transaksi</dt>
            <dd class="approval-modal__value" data-finance-modal-field="id"></dd>
            <dt class="approval-modal__label">{{ $actorLabel }}</dt>
            <dd class="approval-modal__value" data-finance-modal-field="agent"></dd>
            <dt class="approval-modal__label">Nominal</dt>
            <dd class="approval-modal__value" data-finance-modal-field="nominal"></dd>
            <dt class="approval-modal__label">Bank Tujuan</dt>
            <dd class="approval-modal__value" data-finance-modal-field="bank"></dd>
        </dl>

        <p class="approval-modal__note">{{ $note }}</p>

        <div class="approval-modal__actions">
            <button class="approval-modal__button approval-modal__button--cancel" type="button" data-finance-modal-close>Batal</button>
            <button class="approval-modal__button approval-modal__button--confirm" type="button" data-finance-modal-confirm>{{ $confirmLabel }}</button>
        </div>
    </div>
</div>
