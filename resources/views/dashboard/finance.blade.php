@php
    $transactions = [
        [
            'id' => 'WD-20230914-0042',
            'agent' => 'Budi Sentosa',
            'bank' => 'BCA - 8830129xxx',
            'nominal' => 'Rp 5.200.456',
            'date' => '2 Jan 2026',
            'status' => 'success',
            'status_label' => 'Berhasil',
        ],
        [
            'id' => 'WD-20230914-0042',
            'agent' => 'Santi',
            'bank' => 'Mandiri - 1240098xxx',
            'nominal' => 'Rp 2.450.999',
            'date' => '15 Feb 2026',
            'status' => 'pending',
            'status_label' => 'Pengajuan',
        ],
        [
            'id' => 'WD-20230914-0042',
            'agent' => 'Denny',
            'bank' => 'BSI - 012322xxx',
            'nominal' => 'Rp 1.025.873',
            'date' => '6 Mar 2026',
            'status' => 'rejected',
            'status_label' => 'Ditolak',
            'rejection_reason' => 'Data akun belum lengkap',
            'rejected_at' => '6 Mar 2026, 14:20',
            'rejected_by' => 'Finance Administrator',
        ],
    ];
@endphp

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

        .finance-page {
            display: grid;
            gap: 28px;
            padding: 28px 34px 42px;
        }

        .finance-page__title {
            margin: 0;
            color: #151922;
            font-size: clamp(34px, 4vw, 48px);
            font-weight: 900;
            line-height: 1.04;
            letter-spacing: 0;
        }

        .finance-page__subtitle {
            margin: 8px 0 0;
            color: #4f586b;
            font-size: 22px;
            font-weight: 900;
            line-height: 1.3;
            letter-spacing: 0;
        }

        .finance-page__metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 28px;
        }

        .finance-table-card {
            overflow: hidden;
            border: 1px solid #d9dee9;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 14px 34px rgba(16, 24, 40, .07);
        }

        .finance-table-card__scroll {
            overflow-x: auto;
        }

        .finance-table {
            width: 100%;
            min-width: 1040px;
            border-collapse: collapse;
        }

        .finance-table th {
            height: 92px;
            color: #52617f;
            background: #eef0f3;
            padding: 0 28px;
            text-align: left;
            font-size: 16px;
            font-weight: 900;
            letter-spacing: .08em;
            line-height: 1.25;
            text-transform: uppercase;
            vertical-align: middle;
        }

        .finance-table td {
            height: 128px;
            border-top: 1px solid #edf0f5;
            padding: 0 28px;
            color: #151922;
            font-size: 20px;
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
            min-width: 116px;
            min-height: 48px;
            border: 0;
            border-radius: 8px;
            background: #11bec8;
            color: #ffffff;
            cursor: pointer;
            font: inherit;
            font-size: 24px;
            font-weight: 900;
            letter-spacing: 0;
            padding: 0 22px;
        }

        .finance-table__finish:hover,
        .finance-table__finish:focus-visible {
            background: #0aaab3;
            outline: 0;
        }

        @media (max-width: 1180px) {
            .finance-page__metrics {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 860px) {
            .dashboard-shell {
                flex-direction: column;
            }

            .dashboard-main {
                height: auto;
                min-height: 100vh;
                overflow: visible;
            }

            .finance-page {
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
<body>
    <div class="dashboard-shell">
        <x-dashboard.sidebar :role="$role ?? 'finance'" :active="$active ?? 'finance'" />

        <main class="dashboard-main" aria-label="{{ $title ?? 'Finance' }}">
            <x-dashboard.header :title="$headerTitle ?? 'Finance Views'" :user-name="$userName ?? 'Finance Administrator'" />

            <div class="finance-page">
                <section aria-labelledby="finance-page-title">
                    <h1 class="finance-page__title" id="finance-page-title">Finance Management</h1>
                    <p class="finance-page__subtitle">Meninjau dan mengelola aktivitas dan persetujuan keuangan</p>
                </section>

                <section class="finance-page__metrics" aria-label="Ringkasan finance">
                    <x-dashboard.metric-card
                        title="Total Dana Tersalurkan"
                        value="Rp 45.000.000"
                        icon="check"
                        tone="green"
                        variant="horizontal"
                    />
                    <x-dashboard.metric-card
                        title="Total Dana Tertunda"
                        value="Rp 45.000.000"
                        icon="wallet"
                        tone="blue"
                        variant="horizontal"
                    />
                    <x-dashboard.metric-card
                        title="Jumlah Pencairan Komisi"
                        value="249"
                        icon="clipboard-clock"
                        tone="yellow"
                        variant="horizontal"
                    />
                </section>

                <x-dashboard.filter-bar>
                    <x-dashboard.filter-field label="Cari nama atau ID agent" icon="users" placeholder="Cari Nama atau ID Agent..." />
                    <x-dashboard.filter-field
                        label="Status"
                        type="select"
                        :options="[
                            'all' => 'Semua Status',
                            'success' => 'Berhasil',
                            'pending' => 'Pengajuan',
                            'processing' => 'Diproses',
                            'rejected' => 'Ditolak',
                        ]"
                        icon=""
                    />
                    <x-dashboard.filter-field label="Rentang tanggal" icon="calendar" value="Oct 1 - Oct 31, 2026" />
                </x-dashboard.filter-bar>

                <section class="finance-table-card" aria-label="Transaksi pencairan">
                    <x-dashboard.table-tabs
                        active="agent"
                        :tabs="[
                            ['key' => 'seller', 'label' => 'Transaksi Seller', 'icon' => 'seller'],
                            ['key' => 'agent', 'label' => 'Transaksi Agent', 'icon' => 'agent'],
                        ]"
                    />

                    <div class="finance-table-card__scroll">
                        <table class="finance-table">
                            <thead>
                                <tr>
                                    <th>ID Transaksi</th>
                                    <th>Nama Agent</th>
                                    <th>Bank Tujuan</th>
                                    <th>Nominal</th>
                                    <th>Tanggal<br>Pengajuan</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transactions as $transaction)
                                    <tr
                                        data-transaction-id="{{ $transaction['id'] }}"
                                        data-transaction-agent="{{ $transaction['agent'] }}"
                                        data-transaction-bank="{{ $transaction['bank'] }}"
                                        data-transaction-nominal="{{ $transaction['nominal'] }}"
                                        data-rejection-reason="{{ $transaction['rejection_reason'] ?? '' }}"
                                        data-rejected-at="{{ $transaction['rejected_at'] ?? '' }}"
                                        data-rejected-by="{{ $transaction['rejected_by'] ?? '' }}"
                                    >
                                        <td><span class="finance-table__id">{{ $transaction['id'] }}</span></td>
                                        <td>{{ $transaction['agent'] }}</td>
                                        <td><span class="finance-table__bank">{{ $transaction['bank'] }}</span></td>
                                        <td><span class="finance-table__money">{{ $transaction['nominal'] }}</span></td>
                                        <td>{{ $transaction['date'] }}</td>
                                        <td class="finance-table__status-cell">
                                            <x-dashboard.status-badge :status="$transaction['status']" :label="$transaction['status_label']" />
                                        </td>
                                        <td class="finance-table__actions-cell">
                                            @if ($transaction['status'] === 'pending')
                                                <x-dashboard.approval-actions />
                                            @elseif ($transaction['status'] === 'processing')
                                                <button class="finance-table__finish" type="button" data-finance-complete>Selesai</button>
                                            @elseif ($transaction['status'] === 'rejected')
                                                <button class="finance-table__detail" type="button" data-finance-rejection-detail aria-label="Lihat Detail Penolakan" title="Lihat Detail Penolakan">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/>
                                                        <circle cx="12" cy="12" r="3"/>
                                                    </svg>
                                                </button>
                                            @else
                                                <button class="finance-table__action" type="button" aria-label="Detail transaksi belum tersedia" disabled>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/>
                                                        <circle cx="12" cy="12" r="3"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <x-dashboard.pagination summary="Menampilkan 1-5 dari 24 Transaksi Pencairan" :pages="[1, 2, 3, '...', 5]" :current="1" />
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
    <script>
        (() => {
            const decisions = {
                processing: {
                    status: 'processing',
                    label: 'Diproses',
                },
                success: {
                    status: 'success',
                    label: 'Berhasil',
                },
                reject: {
                    status: 'rejected',
                    label: 'Ditolak',
                },
            };

            const resolvedAction = () => `
                <button class="finance-table__action" type="button" aria-label="Detail transaksi belum tersedia" disabled>
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
            const modalByName = (name) => document.querySelector(`[data-finance-modal="${name}"]`);
            const modals = {
                approval: modalByName('approval'),
                completion: modalByName('completion'),
                rejection: modalByName('rejection'),
                rejectionDetail: modalByName('rejection-detail'),
            };
            let pendingRow = null;
            let activeModal = null;

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

            const currentRejectedAt = () => new Intl.DateTimeFormat('id-ID', {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            }).format(new Date()).replace('.', ':');

            const selectedRejectionReason = () => modals.rejection?.querySelector('input[name="rejection_reason"]:checked');

            const resetRejectionReason = () => {
                const modal = modals.rejection;

                modal?.querySelectorAll('input[name="rejection_reason"]').forEach((input) => {
                    input.checked = false;
                });
                modal?.querySelector('#rejection-modal-error')?.setAttribute('hidden', '');
            };

            const hasSelectedRejectionReason = () => Boolean(
                selectedRejectionReason(),
            );

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

            const applyDecision = (row, decision, nextAction = resolvedAction()) => {
                const badge = row?.querySelector('.status-badge');
                const actions = row?.querySelector('.finance-table__actions-cell');

                if (!decision || !badge || !actions) {
                    return;
                }

                badge.className = `status-badge status-badge--${decision.status}`;
                badge.textContent = decision.label;
                actions.innerHTML = nextAction;
            };

            const applyRejection = () => {
                const reason = selectedRejectionReason();

                if (!pendingRow || !reason) {
                    return;
                }

                pendingRow.dataset.rejectionReason = reason.closest('.rejection-modal__reason')?.textContent.trim() || reason.value;
                pendingRow.dataset.rejectedAt = currentRejectedAt();
                pendingRow.dataset.rejectedBy = @json($userName ?? 'Finance Administrator');
                applyDecision(pendingRow, decisions.reject, rejectionDetailAction());
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

                if (button.dataset.financeDecision === 'approve') {
                    openModal('approval', row);
                    return;
                }

                openModal('rejection', row);
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
                    openModal('rejectionDetail', row);
                }
            });

            Object.entries(modals).forEach(([name, modal]) => {
                modal?.querySelector('[data-finance-modal-confirm]')?.addEventListener('click', () => {
                    if (name === 'rejection' && !hasSelectedRejectionReason()) {
                        modal.querySelector('#rejection-modal-error')?.removeAttribute('hidden');
                        modal.querySelector('input[name="rejection_reason"]')?.focus();
                        return;
                    }

                    if (name === 'rejection') {
                        applyRejection();
                    } else {
                        applyDecision(
                            pendingRow,
                            name === 'completion' ? decisions.success : decisions.processing,
                            name === 'approval' ? finishAction() : resolvedAction(),
                        );
                    }
                    closeModal();
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
        })();
    </script>
</body>
</html>
