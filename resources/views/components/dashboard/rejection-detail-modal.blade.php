@once
    <style>
        .rejection-detail-modal {
            position: fixed;
            inset: 0;
            z-index: 80;
            display: grid;
            place-items: center;
            background: rgba(15, 23, 42, .42);
            padding: 20px;
        }

        .rejection-detail-modal[hidden] {
            display: none;
        }

        .rejection-detail-modal__panel {
            width: min(760px, 100%);
            border-radius: 22px;
            background: #ffffff;
            color: #0b0b0f;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .24);
            padding: 38px 40px 46px;
        }

        .rejection-detail-modal__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            border-bottom: 1px solid #c5c7cc;
            padding-bottom: 18px;
        }

        .rejection-detail-modal__title {
            margin: 0;
            font-size: clamp(32px, 4vw, 46px);
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: 0;
        }

        .rejection-detail-modal__close {
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

        .rejection-detail-modal__close:hover,
        .rejection-detail-modal__close:focus-visible {
            color: #11bec8;
            outline: 0;
        }

        .rejection-detail-modal__close svg {
            width: 40px;
            height: 40px;
        }

        .rejection-detail-modal__description {
            margin: 22px 0 28px;
            color: #5c5c5f;
            font-size: 28px;
            font-weight: 500;
            line-height: 1.25;
            letter-spacing: 0;
        }

        .rejection-detail-modal__details {
            display: grid;
            grid-template-columns: max-content 1fr;
            gap: 18px 20px;
            color: #5c5c5f;
            font-size: 28px;
            font-weight: 900;
            line-height: 1.2;
        }

        .rejection-detail-modal__details dt,
        .rejection-detail-modal__details dd {
            margin: 0;
        }

        .rejection-detail-modal__label {
            min-width: 190px;
        }

        .rejection-detail-modal__value::before {
            content: ": ";
        }

        .rejection-detail-modal__actions {
            display: flex;
            justify-content: center;
            margin-top: 38px;
        }

        .rejection-detail-modal__button {
            width: min(290px, 100%);
            min-height: 76px;
            border: 0;
            border-radius: 12px;
            background: #11bec8;
            color: #ffffff;
            cursor: pointer;
            font: inherit;
            font-size: 28px;
            font-weight: 900;
            letter-spacing: 0;
        }

        .rejection-detail-modal__button:hover,
        .rejection-detail-modal__button:focus-visible {
            background: #0aaab3;
            outline: 0;
        }

        @media (max-width: 680px) {
            .rejection-detail-modal__panel {
                padding: 28px 22px 30px;
            }

            .rejection-detail-modal__description,
            .rejection-detail-modal__details,
            .rejection-detail-modal__button {
                font-size: 20px;
            }

            .rejection-detail-modal__details {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            .rejection-detail-modal__value::before {
                content: "";
            }

            .rejection-detail-modal__button {
                width: 100%;
                min-height: 62px;
            }
        }
    </style>
@endonce

<div class="rejection-detail-modal" hidden data-finance-modal="rejection-detail" role="dialog" aria-modal="true" aria-labelledby="rejection-detail-modal-title">
    <div class="rejection-detail-modal__panel">
        <div class="rejection-detail-modal__header">
            <h2 class="rejection-detail-modal__title" id="rejection-detail-modal-title">Detail Penolakan</h2>
            <button class="rejection-detail-modal__close" type="button" data-finance-modal-close aria-label="Tutup modal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <p class="rejection-detail-modal__description">Informasi penolakan request pencairan dana</p>

        <dl class="rejection-detail-modal__details">
            <dt class="rejection-detail-modal__label">ID Transaksi</dt>
            <dd class="rejection-detail-modal__value" data-finance-modal-field="id"></dd>
            <dt class="rejection-detail-modal__label">Nama Agent</dt>
            <dd class="rejection-detail-modal__value" data-finance-modal-field="agent"></dd>
            <dt class="rejection-detail-modal__label">Nominal</dt>
            <dd class="rejection-detail-modal__value" data-finance-modal-field="nominal"></dd>
            <dt class="rejection-detail-modal__label">Bank Tujuan</dt>
            <dd class="rejection-detail-modal__value" data-finance-modal-field="bank"></dd>
            <dt class="rejection-detail-modal__label">Alasan</dt>
            <dd class="rejection-detail-modal__value" data-finance-modal-field="reason"></dd>
            <dt class="rejection-detail-modal__label">Ditolak Pada</dt>
            <dd class="rejection-detail-modal__value" data-finance-modal-field="rejectedAt"></dd>
            <dt class="rejection-detail-modal__label">Ditolak Oleh</dt>
            <dd class="rejection-detail-modal__value" data-finance-modal-field="rejectedBy"></dd>
        </dl>

        <div class="rejection-detail-modal__actions">
            <button class="rejection-detail-modal__button" type="button" data-finance-modal-close>Tutup</button>
        </div>
    </div>
</div>
