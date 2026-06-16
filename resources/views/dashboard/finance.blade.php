<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $title ?? 'Finance' }} - {{ config('app.name', 'Kresek.in') }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: #151922;
            background: #f8fafc;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .dashboard-shell {
            min-height: 100vh;
            display: flex;
        }

        .dashboard-main {
            flex: 1;
            min-width: 0;
            height: 100vh;
            overflow-y: auto;
            background: #f8fafc;
        }

        .finance-compact .dashboard-sidebar {
            width: 264px;
            padding: 24px 22px 22px;
        }

        .finance-compact .dashboard-sidebar__logo {
            width: 118px;
        }

        .finance-compact .dashboard-sidebar__subtitle {
            font-size: 14px;
        }

        .finance-compact .dashboard-sidebar__nav {
            gap: 8px;
            margin-top: 28px;
        }

        .finance-compact .dashboard-sidebar__item {
            min-height: 48px;
            gap: 14px;
            padding: 0 16px;
            font-size: 14px;
        }

        .finance-compact .dashboard-sidebar__icon {
            width: 24px;
            height: 24px;
        }

        .finance-compact .dashboard-sidebar__bottom {
            gap: 12px;
        }

        .finance-compact .dashboard-sidebar__utility,
        .finance-compact .dashboard-sidebar__logout {
            min-height: 36px;
            gap: 14px;
            font-size: 14px;
        }

        .finance-compact .dashboard-header {
            min-height: 64px;
            padding: 0 34px;
        }

        .finance-compact .dashboard-header__title {
            font-size: 20px;
        }

        .finance-compact .dashboard-header__divider {
            height: 26px;
        }

        .finance-compact .dashboard-header__right {
            gap: 14px;
        }

        .finance-compact .dashboard-header__notification {
            width: 36px;
            height: 36px;
        }

        .finance-compact .dashboard-header__icon {
            width: 21px;
            height: 21px;
        }

        .finance-compact .dashboard-header__user {
            min-height: 44px;
            gap: 10px;
            padding: 6px 18px 6px 7px;
            font-size: 13px;
        }

        .finance-compact .dashboard-header__avatar {
            width: 32px;
            height: 32px;
        }

        .finance-compact .dashboard-header__avatar svg {
            width: 18px;
            height: 18px;
        }

        .finance-page {
            display: grid;
            gap: 16px;
            min-height: calc(100vh - 64px);
            grid-template-rows: auto auto auto minmax(0, 1fr);
            padding: 22px 34px 20px;
        }

        .finance-page__title {
            margin: 0;
            color: #151922;
            font-size: clamp(26px, 2.5vw, 34px);
            font-weight: 900;
            line-height: 1.04;
            letter-spacing: 0;
        }

        .finance-page__subtitle {
            margin: 6px 0 0;
            color: #4f586b;
            font-size: 16px;
            font-weight: 900;
            line-height: 1.3;
            letter-spacing: 0;
        }

        .finance-page__metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .finance-compact .metric-card {
            min-height: 118px;
            gap: 12px;
            border-radius: 10px;
            padding: 22px 30px;
        }

        .finance-compact .metric-card--horizontal {
            min-height: 118px;
            gap: 18px;
        }

        .finance-compact .metric-card__label {
            margin-bottom: 8px;
            font-size: 13px;
        }

        .finance-compact .metric-card__value {
            font-size: clamp(24px, 2.2vw, 32px);
        }

        .finance-compact .icon-tile {
            width: 44px;
            height: 44px;
            border-radius: 9px;
        }

        .finance-compact .icon-tile__icon {
            width: 23px;
            height: 23px;
        }

        .finance-compact .filter-bar {
            gap: 18px;
            border-radius: 10px;
            padding: 16px 24px;
        }

        .finance-compact .filter-bar__fields {
            grid-template-columns: minmax(260px, 430px) minmax(170px, 220px) minmax(240px, 260px);
            gap: 16px;
        }

        .finance-compact .filter-bar__button,
        .finance-compact .filter-field__control {
            height: 42px;
            font-size: 13px;
        }

        .finance-compact .filter-field__control {
            padding-right: 40px;
            padding-left: 42px;
        }

        .finance-compact .filter-field--plain .filter-field__control {
            padding-left: 20px;
        }

        .finance-compact .filter-field__icon,
        .finance-compact .filter-field__chevron {
            width: 20px;
            height: 20px;
        }

        .finance-compact .filter-field__icon {
            left: 14px;
        }

        .finance-compact .filter-field__chevron {
            right: 14px;
        }

        .finance-table-card {
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #d9dee9;
            border-radius: 10px;
            background: #ffffff;
            box-shadow: 0 14px 34px rgba(16, 24, 40, .07);
        }

        .finance-table-card__scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
        }

        .finance-compact .table-tabs {
            flex: 0 0 auto;
            min-height: 52px;
            gap: 28px;
            padding: 0 28px;
        }

        .finance-compact .table-tabs__item {
            min-height: 52px;
            gap: 9px;
            font-size: 14px;
        }

        .finance-compact .table-tabs__icon {
            width: 19px;
            height: 19px;
        }

        .finance-table {
            width: 100%;
            min-width: 1040px;
            border-collapse: collapse;
        }

        .finance-table th {
            height: 60px;
            color: #52617f;
            background: #eef0f3;
            padding: 0 28px;
            text-align: left;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .08em;
            line-height: 1.25;
            text-transform: uppercase;
            vertical-align: middle;
        }

        .finance-table td {
            height: 112px;
            border-top: 1px solid #edf0f5;
            padding: 0 28px;
            color: #151922;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0;
            vertical-align: middle;
        }

        .finance-table__id {
            color: #424a5d;
            font-weight: 900;
            line-height: 1.08;
            letter-spacing: .04em;
        }

        .finance-table__bank {
            display: inline-block;
            max-width: 180px;
            line-height: 1.25;
        }

        .finance-table__money {
            white-space: nowrap;
        }

        .finance-table__status-cell,
        .finance-table__actions-cell {
            white-space: nowrap;
        }

        .finance-table__action {
            width: 42px;
            height: 42px;
            display: inline-grid;
            place-items: center;
            border: 1px solid #d7dce7;
            border-radius: 8px;
            background: #ffffff;
            color: #6b7487;
            cursor: not-allowed;
        }

        .finance-table__action svg {
            width: 20px;
            height: 20px;
        }

        .finance-table__detail {
            width: 42px;
            height: 42px;
            display: inline-grid;
            place-items: center;
            border: 1px solid #d7dce7;
            border-radius: 8px;
            background: #ffffff;
            color: #4f586b;
            cursor: pointer;
        }

        .finance-table__detail:hover,
        .finance-table__detail:focus-visible {
            border-color: #11bec8;
            color: #11bec8;
            outline: 0;
        }

        .finance-table__detail svg {
            width: 20px;
            height: 20px;
        }

        .finance-table__finish {
            min-width: 92px;
            min-height: 38px;
            border: 0;
            border-radius: 8px;
            background: #11bec8;
            color: #ffffff;
            cursor: pointer;
            font: inherit;
            font-size: 15px;
            font-weight: 900;
            letter-spacing: 0;
            padding: 0 18px;
        }

        .finance-table__finish:hover,
        .finance-table__finish:focus-visible {
            background: #0aaab3;
            outline: 0;
        }

        .finance-page__error {
            display: none;
            border: 1px solid #fecaca;
            border-radius: 10px;
            background: #fff1f2;
            color: #b91c1c;
            font-size: 16px;
            font-weight: 900;
            padding: 16px 18px;
        }

        .finance-page__error.is-visible {
            display: block;
        }

        .finance-skeleton-line {
            display: inline-block;
            width: 100%;
            height: 10px;
            border-radius: 999px;
            background: linear-gradient(90deg, #e2e8f0, #f8fafc, #e2e8f0);
            background-size: 200% 100%;
            animation: finance-loading-shimmer 1.2s ease-in-out infinite;
        }

        .finance-skeleton-line--sm {
            width: 48px;
        }

        .finance-skeleton-line--md {
            width: 96px;
        }

        .finance-skeleton-line--lg {
            width: 132px;
        }

        .finance-table__loading-row td {
            height: 52px;
        }

        .finance-table__empty {
            height: 98px;
            color: #52617f;
            font-size: 15px;
            font-weight: 800;
            text-align: center;
        }

        .finance-compact .dashboard-pagination {
            flex: 0 0 auto;
            gap: 16px;
            padding: 18px 28px;
        }

        .finance-compact .dashboard-pagination__summary {
            font-size: 13px;
        }

        .finance-compact .dashboard-pagination__controls {
            gap: 10px;
        }

        .finance-compact .dashboard-pagination__button,
        .finance-compact .dashboard-pagination__page {
            width: 38px;
            height: 38px;
            font-size: 12px;
        }

        .finance-compact .dashboard-pagination__icon {
            width: 20px;
            height: 20px;
        }

        .metric-card.is-loading .metric-card__value {
            width: min(180px, 68%);
            min-height: 24px;
            color: transparent;
            border-radius: 999px;
            background: linear-gradient(90deg, #e2e8f0, #f8fafc, #e2e8f0);
            background-size: 200% 100%;
            animation: finance-loading-shimmer 1.2s ease-in-out infinite;
        }

        .metric-card.is-loading .icon-tile {
            width: 34px;
            height: 34px;
            color: transparent;
            background: linear-gradient(90deg, #e2e8f0, #f8fafc, #e2e8f0);
            background-size: 200% 100%;
            animation: finance-loading-shimmer 1.2s ease-in-out infinite;
        }

        .metric-card.is-loading .icon-tile__icon {
            width: 18px;
            height: 18px;
        }

        .filter-bar.is-loading {
            opacity: .68;
        }

        .filter-bar.is-loading .filter-field__control,
        .filter-bar.is-loading .filter-bar__button {
            cursor: wait;
        }

        @keyframes finance-loading-shimmer {
            0% {
                background-position: 100% 0;
            }

            100% {
                background-position: -100% 0;
            }
        }

        @media (max-width: 1180px) {
            .finance-page__metrics {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 860px) {
            .finance-compact .dashboard-sidebar {
                width: 100%;
                padding: 22px 18px;
            }

            .finance-compact .dashboard-header {
                min-height: auto;
                padding: 18px;
            }

            .dashboard-shell {
                flex-direction: column;
            }

            .dashboard-main {
                height: auto;
                min-height: 100vh;
                overflow: visible;
            }

            .finance-page {
                min-height: auto;
                grid-template-rows: none;
                padding: 22px 18px 32px;
            }

            .finance-table th,
            .finance-table td {
                padding-right: 22px;
                padding-left: 22px;
            }
        }
    </style>
</head>
<body class="finance-compact">
    <div class="dashboard-shell">
        <x-dashboard.sidebar :role="$role ?? 'finance'" :active="$active ?? 'finance'" />

        <main class="dashboard-main" aria-label="{{ $title ?? 'Finance' }}">
            <x-dashboard.header :title="$headerTitle ?? 'Finance Views'" :user-name="$userName ?? 'Finance Administrator'" />

            <div class="finance-page" data-finance-page>
                <section aria-labelledby="finance-page-title">
                    <h1 class="finance-page__title" id="finance-page-title">Finance Management</h1>
                    <p class="finance-page__subtitle">Meninjau dan mengelola aktivitas dan persetujuan keuangan</p>
                </section>

                <div class="finance-page__error" data-finance-error role="status"></div>

                <section class="finance-page__metrics" aria-label="Ringkasan finance">
                    <x-dashboard.metric-card
                        class="is-loading"
                        aria-busy="true"
                        data-finance-metric="disbursed"
                        title="Total Dana Tersalurkan"
                        value="-"
                        icon="check"
                        tone="green"
                        variant="horizontal"
                    />
                    <x-dashboard.metric-card
                        class="is-loading"
                        aria-busy="true"
                        data-finance-metric="pending"
                        title="Total Dana Tertunda"
                        value="-"
                        icon="wallet"
                        tone="blue"
                        variant="horizontal"
                    />
                    <x-dashboard.metric-card
                        class="is-loading"
                        aria-busy="true"
                        data-finance-metric="withdrawals"
                        title="Jumlah Pencairan Komisi"
                        value="-"
                        icon="clipboard-clock"
                        tone="yellow"
                        variant="horizontal"
                    />
                </section>

                <x-dashboard.filter-bar class="is-loading" data-finance-filter aria-busy="true">
                    <x-dashboard.filter-field data-finance-search label="Cari nama atau ID agent" icon="users" placeholder="Cari Nama atau ID Agent..." />
                    <x-dashboard.filter-field
                        data-finance-status
                        label="Status"
                        type="select"
                        :options="[
                            'all' => 'Semua Status',
                            'paid' => 'Berhasil',
                            'requested' => 'Pengajuan',
                            'approved' => 'Diproses',
                            'rejected' => 'Ditolak',
                        ]"
                        icon=""
                    />
                    <x-dashboard.filter-field data-finance-date-range label="Rentang tanggal" icon="calendar" placeholder="YYYY-MM-DD - YYYY-MM-DD" />
                </x-dashboard.filter-bar>

                <section class="finance-table-card is-loading" data-finance-table-card aria-label="Transaksi pencairan" aria-busy="true">
                    <x-dashboard.table-tabs
                        active="seller"
                        :tabs="[
                            ['key' => 'seller', 'label' => 'Transaksi Seller', 'icon' => 'seller'],
                            ['key' => 'agent', 'label' => 'Transaksi Agent', 'icon' => 'agent'],
                        ]"
                    />

                    <div class="finance-table-card__scroll">
                        <table class="finance-table">
                            <thead data-finance-table-head>
                                <tr>
                                    <th>ID Transaksi</th>
                                    <th>Nama UMKM</th>
                                    <th>Bank Tujuan</th>
                                    <th>Nominal</th>
                                    <th>Tanggal<br>Pengajuan</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody data-finance-transactions>
                                @for ($index = 0; $index < 5; $index++)
                                    <tr class="finance-table__loading-row">
                                        <td><span class="finance-skeleton-line finance-skeleton-line--md"></span></td>
                                        <td><span class="finance-skeleton-line finance-skeleton-line--md"></span></td>
                                        <td><span class="finance-skeleton-line finance-skeleton-line--lg"></span></td>
                                        <td><span class="finance-skeleton-line finance-skeleton-line--md"></span></td>
                                        <td><span class="finance-skeleton-line finance-skeleton-line--sm"></span></td>
                                        <td><span class="finance-skeleton-line finance-skeleton-line--sm"></span></td>
                                        <td><span class="finance-skeleton-line finance-skeleton-line--sm"></span></td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>

                    <x-dashboard.pagination data-finance-pagination summary="Memuat Transaksi Pencairan" :pages="[1]" :current="1" />
                </section>
            </div>
        </main>
    </div>
    <x-dashboard.approval-confirmation-modal />
    <x-dashboard.approval-confirmation-modal
        name="completion"
        title="Selesaikan Pencairan Dana"
        description="Anda akan menyelesaikan pencairan dana agent berikut"
        note="Pastikan dana sudah terkirim ke rekening tujuan sebelum menyelesaikan pencairan dana"
        confirm-label="Ya, Selesai"
    />
    <x-dashboard.rejection-confirmation-modal />
    <x-dashboard.rejection-detail-modal />
    <div hidden aria-hidden="true">
        <x-dashboard.approval-actions />
        <x-dashboard.status-badge status="pending" label="Pengajuan" />
    </div>
    <script>
        (() => {
            const resolvedAction = () => `
                <button class="finance-table__action" type="button" aria-label="Transaksi selesai" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            `;
            const rejectionDetailAction = () => `
                <button class="finance-table__detail" type="button" data-finance-rejection-detail aria-label="Lihat Detail Penolakan" title="Lihat Detail Penolakan">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            `;
            const finishAction = () => '<button class="finance-table__finish" type="button" data-finance-complete>Selesai</button>';
            const approvalActions = () => `
                <div class="approval-actions">
                    <button class="approval-actions__button approval-actions__button--approve" type="button" data-finance-decision="approve" aria-label="Disetujui" title="Disetujui">
                        <svg class="approval-actions__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m5 12 4.5 4.5L19 7"/>
                        </svg>
                    </button>
                    <button class="approval-actions__button approval-actions__button--reject" type="button" data-finance-decision="reject" aria-label="Ditolak" title="Ditolak">
                        <svg class="approval-actions__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 6l12 12M18 6 6 18"/>
                        </svg>
                    </button>
                </div>
            `;
            const modalByName = (name) => document.querySelector(`[data-finance-modal="${name}"]`);
            const modals = {
                approval: modalByName('approval'),
                completion: modalByName('completion'),
                rejection: modalByName('rejection'),
                rejectionDetail: modalByName('rejection-detail'),
            };
            const state = {
                page: 1,
                perPage: 5,
                tab: 'seller',
            };
            let pendingRow = null;
            let activeModal = null;

            const tableCard = document.querySelector('[data-finance-table-card]');
            const tableHead = document.querySelector('[data-finance-table-head]');
            const tableBody = document.querySelector('[data-finance-transactions]');
            const errorBox = document.querySelector('[data-finance-error]');
            const filterBar = document.querySelector('[data-finance-filter]');
            const pagination = document.querySelector('[data-finance-pagination]');
            const searchInput = document.querySelector('[data-finance-search] input');
            const statusSelect = document.querySelector('[data-finance-status] select');
            const dateRangeInput = document.querySelector('[data-finance-date-range] input');
            const filterButton = filterBar?.querySelector('.filter-bar__button');
            const token = localStorage.getItem('kresekin_token');
            const tokenType = localStorage.getItem('kresekin_token_type') || 'Bearer';
            const headers = {
                Accept: 'application/json',
                ...(token ? { Authorization: `${tokenType} ${token}` } : {}),
            };
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[char]));
            const valueFrom = (...values) => values.find((value) => value !== undefined && value !== null && value !== '');
            const clearLoading = (element) => {
                element?.classList.remove('is-loading');
                element?.removeAttribute('aria-busy');
            };
            const setControlsDisabled = (disabled) => {
                filterBar?.querySelectorAll('input, select, button').forEach((control) => {
                    control.disabled = disabled;
                });
            };
            const showError = (message) => {
                if (!errorBox) {
                    return;
                }

                errorBox.textContent = message;
                errorBox.classList.add('is-visible');
            };
            const clearError = () => {
                if (!errorBox) {
                    return;
                }

                errorBox.textContent = '';
                errorBox.classList.remove('is-visible');
            };
            const fetchJson = async (url, options = {}) => {
                const response = await fetch(url, {
                    ...options,
                    headers: {
                        ...headers,
                        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                        ...(options.headers || {}),
                    },
                });
                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(payload.message || `Request failed: ${response.status}`);
                }

                return payload;
            };
            const setModalDetails = (modal, row) => {
                if (!modal || !row) {
                    return;
                }

                modal.querySelector('[data-finance-modal-field="id"]').textContent = row.dataset.transactionId || '-';
                modal.querySelector('[data-finance-modal-field="agent"]').textContent = row.dataset.transactionAgent || '-';
                modal.querySelector('[data-finance-modal-field="nominal"]').textContent = row.dataset.transactionNominal || '-';
                modal.querySelector('[data-finance-modal-field="bank"]').textContent = row.dataset.transactionBank || '-';
                modal.querySelector('[data-finance-modal-field="reason"]')?.replaceChildren(document.createTextNode(row.dataset.rejectionReason || '-'));
                modal.querySelector('[data-finance-modal-field="rejectedAt"]')?.replaceChildren(document.createTextNode(row.dataset.rejectedAt || '-'));
                modal.querySelector('[data-finance-modal-field="rejectedBy"]')?.replaceChildren(document.createTextNode(row.dataset.rejectedBy || '-'));
            };
            const selectedRejectionReason = () => modals.rejection?.querySelector('input[name="rejection_reason"]:checked');
            const resetRejectionReason = () => {
                modals.rejection?.querySelectorAll('input[name="rejection_reason"]').forEach((input) => {
                    input.checked = false;
                });
                modals.rejection?.querySelector('#rejection-modal-error')?.setAttribute('hidden', '');
            };
            const openModal = (name, row) => {
                const modal = modals[name];

                if (!modal) {
                    return;
                }

                pendingRow = row;
                activeModal = name;
                setModalDetails(modal, row);
                if (name === 'rejection') {
                    resetRejectionReason();
                }
                modal.hidden = false;
                document.body.style.overflow = 'hidden';
                if (name === 'rejection') {
                    modal.querySelector('input[name="rejection_reason"]')?.focus();
                } else {
                    modal.querySelector('[data-finance-modal-confirm]')?.focus();
                }
            };
            const closeModal = () => {
                const modal = activeModal ? modals[activeModal] : null;

                if (!modal) {
                    return;
                }

                modal.hidden = true;
                document.body.style.overflow = '';
                if (activeModal === 'rejection') {
                    resetRejectionReason();
                }
                pendingRow = null;
                activeModal = null;
            };
            const statusFromApi = (value) => ({
                requested: 'pending',
                approved: 'processing',
                paid: 'success',
                rejected: 'rejected',
            }[String(value || '').toLowerCase()] || 'pending');
            const actionFor = (status) => {
                if (status === 'pending') {
                    return approvalActions();
                }

                if (status === 'processing') {
                    return finishAction();
                }

                if (status === 'rejected') {
                    return rejectionDetailAction();
                }

                return resolvedAction();
            };
            const mapWithdrawal = (item) => {
                const status = statusFromApi(item.status);
                const bankName = valueFrom(item.bank?.name, '-');
                const bankAccount = valueFrom(item.bank?.account_number_masked, '');
                const bankHolder = valueFrom(item.bank?.account_holder, '');

                return {
                    id: valueFrom(item.id, '-'),
                    agent: valueFrom(item.agent?.name, '-'),
                    bank: [bankName, bankAccount, bankHolder].filter((part) => part && part !== '-').join(' - ') || '-',
                    nominal: valueFrom(item.amount_label, '-'),
                    date: valueFrom(item.requested_at_label, '-'),
                    status,
                    statusLabel: valueFrom(item.status_label, 'Pengajuan'),
                    rejectionReason: valueFrom(item.rejection?.reason_label, ''),
                    rejectedAt: valueFrom(item.rejection?.rejected_at_label, ''),
                    rejectedBy: valueFrom(item.rejection?.rejected_by?.name, ''),
                };
            };
            const mapSellerSubmission = (item) => {
                const status = statusFromApi(item.status);
                const bankName = valueFrom(item.bank?.name, '-');
                const bankAccount = valueFrom(item.bank?.account_number_masked, '');

                return {
                    id: valueFrom(item.id, '-'),
                    agent: valueFrom(item.store?.name, '-'),
                    bank: [bankName, bankAccount].filter((part) => part && part !== '-').join(' - ') || '-',
                    nominal: valueFrom(item.amount_label, '-'),
                    date: valueFrom(item.requested_at_label, '-'),
                    status,
                    statusLabel: valueFrom(item.status_label, 'Pengajuan'),
                    rejectionReason: '',
                    rejectedAt: '',
                    rejectedBy: '',
                };
            };
            const renderTableHeader = () => {
                if (!tableHead) {
                    return;
                }

                const nameColumn = state.tab === 'seller' ? 'Nama UMKM' : 'Nama Agent';

                tableHead.innerHTML = `
                    <tr>
                        <th>ID Transaksi</th>
                        <th>${nameColumn}</th>
                        <th>Bank Tujuan</th>
                        <th>Nominal</th>
                        <th>Tanggal<br>Pengajuan</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                `;
            };
            const renderMetric = (key, value) => {
                const card = document.querySelector(`[data-finance-metric="${key}"]`);
                const target = card?.querySelector('.metric-card__value');

                if (target) {
                    target.textContent = value ?? '-';
                }

                clearLoading(card);
            };
            const renderMetrics = (summary) => {
                renderMetric('disbursed', valueFrom(summary?.total_disbursed_label, '-'));
                renderMetric('pending', valueFrom(summary?.total_pending_label, '-'));
                renderMetric('withdrawals', valueFrom(summary?.total_withdrawals, 0));
            };
            const renderTableLoading = () => {
                if (!tableBody) {
                    return;
                }

                tableCard?.classList.add('is-loading');
                tableCard?.setAttribute('aria-busy', 'true');
                tableBody.innerHTML = Array.from({ length: state.perPage }, () => `
                    <tr class="finance-table__loading-row">
                        <td><span class="finance-skeleton-line finance-skeleton-line--md"></span></td>
                        <td><span class="finance-skeleton-line finance-skeleton-line--md"></span></td>
                        <td><span class="finance-skeleton-line finance-skeleton-line--lg"></span></td>
                        <td><span class="finance-skeleton-line finance-skeleton-line--md"></span></td>
                        <td><span class="finance-skeleton-line finance-skeleton-line--sm"></span></td>
                        <td><span class="finance-skeleton-line finance-skeleton-line--sm"></span></td>
                        <td><span class="finance-skeleton-line finance-skeleton-line--sm"></span></td>
                    </tr>
                `).join('');
            };
            const renderRows = (rows) => {
                if (!tableBody) {
                    return;
                }

                if (!rows.length) {
                    tableBody.innerHTML = '<tr><td class="finance-table__empty" colspan="7">Belum ada transaksi pencairan.</td></tr>';
                    return;
                }

                tableBody.innerHTML = rows.map((row) => `
                    <tr
                        data-transaction-id="${escapeHtml(row.id)}"
                        data-transaction-agent="${escapeHtml(row.agent)}"
                        data-transaction-bank="${escapeHtml(row.bank)}"
                        data-transaction-nominal="${escapeHtml(row.nominal)}"
                        data-rejection-reason="${escapeHtml(row.rejectionReason)}"
                        data-rejected-at="${escapeHtml(row.rejectedAt)}"
                        data-rejected-by="${escapeHtml(row.rejectedBy)}"
                    >
                        <td><span class="finance-table__id">${escapeHtml(row.id)}</span></td>
                        <td>${escapeHtml(row.agent)}</td>
                        <td><span class="finance-table__bank">${escapeHtml(row.bank)}</span></td>
                        <td><span class="finance-table__money">${escapeHtml(row.nominal)}</span></td>
                        <td>${escapeHtml(row.date)}</td>
                        <td class="finance-table__status-cell"><span class="status-badge status-badge--${escapeHtml(row.status)}">${escapeHtml(row.statusLabel)}</span></td>
                        <td class="finance-table__actions-cell">${state.tab === 'agent' ? actionFor(row.status) : ''}</td>
                    </tr>
                `).join('');
            };
            const pageLink = (label, current = false) => `
                <a class="dashboard-pagination__page ${current ? 'is-current' : ''}" href="#" data-finance-page-link="${label}" ${current ? 'aria-current="page"' : ''}>${label}</a>
            `;
            const renderPagination = (meta = {}) => {
                const summary = pagination?.querySelector('.dashboard-pagination__summary');
                const controls = pagination?.querySelector('.dashboard-pagination__controls');
                const current = Number(meta.current_page || 1);
                const last = Number(meta.last_page || 1);
                const total = Number(meta.total || 0);
                const from = meta.from || (total ? 1 : 0);
                const to = meta.to || total;

                if (summary) {
                    summary.textContent = total
                        ? `Menampilkan ${from}-${to} dari ${total} Transaksi Pencairan`
                        : 'Menampilkan 0 Transaksi Pencairan';
                }

                if (!controls) {
                    return;
                }

                const pages = Array.from({ length: Math.min(last, 5) }, (_, index) => index + 1);
                controls.innerHTML = `
                    <a class="dashboard-pagination__button" href="#" data-finance-page-link="${Math.max(1, current - 1)}" aria-disabled="${current <= 1}" aria-label="Halaman sebelumnya">
                        <svg class="dashboard-pagination__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    </a>
                    ${pages.map((pageNumber) => pageLink(pageNumber, pageNumber === current)).join('')}
                    ${last > 5 ? '<span class="dashboard-pagination__ellipsis">...</span>' : ''}
                    ${last > 5 ? pageLink(last, last === current) : ''}
                    <a class="dashboard-pagination__button" href="#" data-finance-page-link="${Math.min(last, current + 1)}" aria-disabled="${current >= last}" aria-label="Halaman berikutnya">
                        <svg class="dashboard-pagination__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                `;
            };
            const markCurrentPaginationPage = (page) => {
                pagination?.querySelectorAll('.dashboard-pagination__page').forEach((pageLink) => {
                    const isCurrent = Number(pageLink.dataset.financePageLink || 0) === page;

                    pageLink.classList.toggle('is-current', isCurrent);
                    if (isCurrent) {
                        pageLink.setAttribute('aria-current', 'page');
                    } else {
                        pageLink.removeAttribute('aria-current');
                    }
                });
            };
            const queryParams = () => {
                const params = new URLSearchParams({
                    page: state.page,
                    per_page: state.perPage,
                });
                const search = searchInput?.value.trim();
                const status = statusSelect?.value;
                const dateRange = dateRangeInput?.value.trim();

                if (search) {
                    params.set('search', search);
                }

                if (status && status !== 'all') {
                    params.set('status', status);
                }

                if (dateRange) {
                    const [from, to] = dateRange.split(/\s+-\s+/);

                    if (from) {
                        params.set('date_from', from.trim());
                    }

                    if (to) {
                        params.set('date_to', to.trim());
                    }
                }

                return params;
            };
            const setActiveTab = (tab) => {
                state.tab = tab;
                state.page = 1;
                state.perPage = tab === 'seller' ? 5 : 10;

                document.querySelectorAll('[data-table-tab]').forEach((button) => {
                    const isActive = button.dataset.tableTab === tab;

                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });
            };
            const loadFinancePage = async ({ showSellerTableLoading = false } = {}) => {
                clearError();
                setControlsDisabled(true);
                renderTableHeader();
                if (showSellerTableLoading && state.tab === 'seller') {
                    renderTableLoading();
                }

                try {
                    const listUrl = state.tab === 'seller'
                        ? `/api/finance/seller-transaction-submissions?${queryParams()}`
                        : `/api/finance/commission-withdrawals?${queryParams()}`;
                    const [summaryPayload, listPayload] = await Promise.all([
                        fetchJson('/api/finance/commission-withdrawals/summary'),
                        fetchJson(listUrl),
                    ]);
                    const rows = (Array.isArray(listPayload?.data) ? listPayload.data : [])
                        .map(state.tab === 'seller' ? mapSellerSubmission : mapWithdrawal);

                    renderMetrics(summaryPayload?.data || {});
                    renderRows(rows);
                    renderPagination(listPayload?.meta || { total: rows.length });
                    clearLoading(tableCard);
                    clearLoading(filterBar);
                    setControlsDisabled(false);
                } catch (error) {
                    renderMetric('disbursed', '-');
                    renderMetric('pending', '-');
                    renderMetric('withdrawals', '-');
                    renderRows([]);
                    renderPagination({ total: 0 });
                    clearLoading(tableCard);
                    clearLoading(filterBar);
                    setControlsDisabled(false);
                    showError(error.message || 'Gagal memuat data finance. Silakan coba lagi.');
                }
            };
            const mutateWithdrawal = async (row, action, body = null) => {
                if (!row?.dataset.transactionId) {
                    return;
                }

                setControlsDisabled(true);
                clearError();

                try {
                    await fetchJson(`/api/finance/commission-withdrawals/${encodeURIComponent(row.dataset.transactionId)}/${action}`, {
                        method: 'PATCH',
                        ...(body ? { body: JSON.stringify(body) } : {}),
                    });
                    closeModal();
                    await loadFinancePage();
                } catch (error) {
                    setControlsDisabled(false);
                    showError(error.message || 'Gagal memperbarui status pencairan.');
                }
            };
            const loadRejectionDetail = async (row) => {
                if (!row?.dataset.transactionId) {
                    return;
                }

                try {
                    const payload = await fetchJson(`/api/finance/commission-withdrawals/${encodeURIComponent(row.dataset.transactionId)}`);
                    const detail = mapWithdrawal(payload?.data || {});

                    row.dataset.rejectionReason = detail.rejectionReason;
                    row.dataset.rejectedAt = detail.rejectedAt;
                    row.dataset.rejectedBy = detail.rejectedBy;
                    openModal('rejectionDetail', row);
                } catch (error) {
                    showError(error.message || 'Gagal memuat detail penolakan.');
                }
            };

            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-finance-decision]');

                if (!button) {
                    return;
                }

                const row = button.closest('tr');

                if (!row) {
                    return;
                }

                openModal(button.dataset.financeDecision === 'approve' ? 'approval' : 'rejection', row);
            });
            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-finance-complete]');

                if (!button) {
                    return;
                }

                const row = button.closest('tr');

                if (row) {
                    openModal('completion', row);
                }
            });
            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-finance-rejection-detail]');

                if (!button) {
                    return;
                }

                const row = button.closest('tr');

                if (row) {
                    loadRejectionDetail(row);
                }
            });
            Object.entries(modals).forEach(([name, modal]) => {
                modal?.querySelector('[data-finance-modal-confirm]')?.addEventListener('click', () => {
                    if (name === 'rejection' && !selectedRejectionReason()) {
                        modal.querySelector('#rejection-modal-error')?.removeAttribute('hidden');
                        modal.querySelector('input[name="rejection_reason"]')?.focus();
                        return;
                    }

                    if (name === 'rejection') {
                        mutateWithdrawal(pendingRow, 'reject', { reason: selectedRejectionReason().value });
                        return;
                    }

                    mutateWithdrawal(pendingRow, name === 'completion' ? 'mark-as-paid' : 'approve');
                });
                modal?.addEventListener('click', (event) => {
                    if (event.target === modal || event.target.closest('[data-finance-modal-close]')) {
                        closeModal();
                    }
                });

                if (name === 'rejection') {
                    modal?.addEventListener('change', (event) => {
                        if (event.target.matches('input[name="rejection_reason"]')) {
                            modal.querySelector('#rejection-modal-error')?.setAttribute('hidden', '');
                        }
                    });
                }
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && activeModal) {
                    closeModal();
                }
            });
            filterButton?.addEventListener('click', () => {
                state.page = 1;
                loadFinancePage();
            });
            filterBar?.addEventListener('submit', (event) => {
                event.preventDefault();
                state.page = 1;
                loadFinancePage();
            });
            document.querySelectorAll('[data-table-tab]').forEach((button) => {
                button.addEventListener('click', () => {
                    const tab = button.dataset.tableTab;

                    if (!tab || tab === state.tab) {
                        return;
                    }

                    setActiveTab(tab);
                    loadFinancePage();
                });
            });
            pagination?.addEventListener('click', (event) => {
                const link = event.target.closest('[data-finance-page-link]');

                if (!link || link.getAttribute('aria-disabled') === 'true') {
                    event.preventDefault();
                    return;
                }

                event.preventDefault();
                state.page = Number(link.dataset.financePageLink || 1);
                markCurrentPaginationPage(state.page);
                loadFinancePage({ showSellerTableLoading: state.tab === 'seller' });
            });

            setActiveTab(state.tab);
            loadFinancePage();
        })();
    </script>
</body>
</html>
