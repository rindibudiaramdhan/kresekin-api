@props([
    'reasons' => [
        'invalid_account' => 'Data rekening tidak valid',
        'insufficient_balance' => 'Saldo tidak mencukupi',
        'incomplete_account' => 'Data akun belum lengkap',
        'suspicious_activity' => 'Sistem mendeteksi aktivitas tidak wajar',
    ],
])

@once
    <style>
        .rejection-modal {
            position: fixed;
            inset: 0;
            z-index: 80;
            display: grid;
            place-items: center;
            background: rgba(15, 23, 42, .42);
            padding: 20px;
        }

        .rejection-modal[hidden] {
            display: none;
        }

        .rejection-modal__panel {
            width: min(760px, 100%);
            border-radius: 22px;
            background: #ffffff;
            color: #0b0b0f;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .24);
            padding: 38px 40px 46px;
        }

        .rejection-modal__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            border-bottom: 1px solid #c5c7cc;
            padding-bottom: 18px;
        }

        .rejection-modal__title {
            margin: 0;
            font-size: clamp(32px, 4vw, 46px);
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: 0;
        }

        .rejection-modal__close {
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

        .rejection-modal__close:hover,
        .rejection-modal__close:focus-visible {
            color: #11bec8;
            outline: 0;
        }

        .rejection-modal__close svg {
            width: 40px;
            height: 40px;
        }

        .rejection-modal__description {
            margin: 22px 0 28px;
            color: #5c5c5f;
            font-size: 28px;
            font-weight: 500;
            line-height: 1.25;
            letter-spacing: 0;
        }

        .rejection-modal__details {
            display: grid;
            grid-template-columns: max-content 1fr;
            gap: 18px 20px;
            color: #5c5c5f;
            font-size: 28px;
            font-weight: 900;
            line-height: 1.2;
        }

        .rejection-modal__details dt,
        .rejection-modal__details dd {
            margin: 0;
        }

        .rejection-modal__label {
            min-width: 176px;
        }

        .rejection-modal__value::before {
            content: ": ";
        }

        .rejection-modal__reason-title {
            margin: 20px 0 18px;
            color: #5c5c5f;
            font-size: 28px;
            font-weight: 900;
            line-height: 1.2;
        }

        .rejection-modal__reasons {
            display: grid;
            gap: 16px;
            border: 0;
            margin: 0;
            padding: 0;
        }

        .rejection-modal__reason {
            display: inline-flex;
            align-items: center;
            gap: 26px;
            color: #5c5c5f;
            cursor: pointer;
            font-size: 27px;
            font-weight: 900;
            line-height: 1.15;
        }

        .rejection-modal__radio {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .rejection-modal__radio-mark {
            width: 34px;
            height: 34px;
            display: inline-grid;
            place-items: center;
            flex: 0 0 auto;
            border: 2px solid #e00000;
            border-radius: 50%;
            color: transparent;
            background: #ffffff;
        }

        .rejection-modal__radio:checked + .rejection-modal__radio-mark {
            color: #ffffff;
            background: #e00000;
        }

        .rejection-modal__radio:focus-visible + .rejection-modal__radio-mark {
            box-shadow: 0 0 0 4px rgba(224, 0, 0, .14);
        }

        .rejection-modal__radio-mark svg {
            width: 22px;
            height: 22px;
        }

        .rejection-modal__error {
            min-height: 22px;
            margin: 12px 0 16px;
            color: #c52121;
            font-size: 16px;
            font-weight: 900;
        }

        .rejection-modal__error[hidden] {
            display: block;
            visibility: hidden;
        }

        .rejection-modal__actions {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .rejection-modal__button {
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

        .rejection-modal__button--cancel {
            background: #c6c6c9;
        }

        .rejection-modal__button--confirm {
            background: #e00000;
        }

        .rejection-modal__button:hover,
        .rejection-modal__button:focus-visible {
            filter: brightness(.96);
            outline: 0;
        }

        @media (max-width: 680px) {
            .rejection-modal__panel {
                padding: 28px 22px 30px;
            }

            .rejection-modal__description,
            .rejection-modal__details,
            .rejection-modal__reason-title,
            .rejection-modal__reason,
            .rejection-modal__button {
                font-size: 20px;
            }

            .rejection-modal__details {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            .rejection-modal__value::before {
                content: "";
            }

            .rejection-modal__actions {
                flex-direction: column;
            }

            .rejection-modal__button {
                width: 100%;
                min-height: 62px;
            }
        }
    </style>
@endonce

<div class="rejection-modal" hidden data-finance-modal="rejection" role="dialog" aria-modal="true" aria-labelledby="rejection-modal-title">
    <div class="rejection-modal__panel">
        <div class="rejection-modal__header">
            <h2 class="rejection-modal__title" id="rejection-modal-title">Tolak Pencairan Dana</h2>
            <button class="rejection-modal__close" type="button" data-finance-modal-close aria-label="Tutup modal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <p class="rejection-modal__description">Anda akan menolak request pencairan dana berikut</p>

        <dl class="rejection-modal__details">
            <dt class="rejection-modal__label">ID Transaksi</dt>
            <dd class="rejection-modal__value" data-finance-modal-field="id"></dd>
            <dt class="rejection-modal__label">Nama Agent</dt>
            <dd class="rejection-modal__value" data-finance-modal-field="agent"></dd>
            <dt class="rejection-modal__label">Nominal</dt>
            <dd class="rejection-modal__value" data-finance-modal-field="nominal"></dd>
            <dt class="rejection-modal__label">Bank Tujuan</dt>
            <dd class="rejection-modal__value" data-finance-modal-field="bank"></dd>
        </dl>

        <div class="rejection-modal__reason-title">Alasan Penolakan</div>

        <fieldset class="rejection-modal__reasons" aria-describedby="rejection-modal-error">
            @foreach ($reasons as $value => $label)
                <label class="rejection-modal__reason">
                    <input class="rejection-modal__radio" type="radio" name="rejection_reason" value="{{ $value }}" required>
                    <span class="rejection-modal__radio-mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m5 12 4.5 4.5L19 7"/>
                        </svg>
                    </span>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </fieldset>

        <p class="rejection-modal__error" id="rejection-modal-error" hidden>Alasan penolakan wajib dipilih.</p>

        <div class="rejection-modal__actions">
            <button class="rejection-modal__button rejection-modal__button--cancel" type="button" data-finance-modal-close>Batal</button>
            <button class="rejection-modal__button rejection-modal__button--confirm" type="button" data-finance-modal-confirm>Tolak Pengajuan</button>
        </div>
    </div>
</div>
