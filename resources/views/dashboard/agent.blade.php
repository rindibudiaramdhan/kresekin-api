<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $title ?? 'Dashboard Agent' }} - {{ config('app.name', 'Kresek.in') }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            color: #17202f;
            background: #f6f8fb;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        button, input { font: inherit; }

        .agent-shell {
            min-height: 100vh;
            display: flex;
        }

        .agent-main {
            flex: 1;
            min-width: 0;
            height: 100vh;
            overflow-y: auto;
            background: #f6f8fb;
        }

        .agent-content {
            display: grid;
            gap: 24px;
            padding: 28px 34px 36px;
        }

        .agent-toolbar {
            display: flex;
            justify-content: flex-end;
        }

        .period-control {
            display: inline-flex;
            gap: 6px;
            border: 1px solid #dbe2ec;
            border-radius: 10px;
            background: #ffffff;
            padding: 5px;
        }

        .period-control__button {
            min-width: 96px;
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: #5d687d;
            padding: 10px 14px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 900;
        }

        .period-control__button.is-active {
            color: #ffffff;
            background: #11bec8;
        }

        .agent-summary-grid {
            display: grid;
            grid-template-columns: minmax(280px, .9fr) minmax(0, 1.6fr);
            gap: 24px;
            align-items: stretch;
        }

        .commission-card {
            min-height: 258px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-radius: 8px;
            background: #11bec8;
            color: #ffffff;
            padding: 28px;
            box-shadow: 0 18px 36px rgba(17, 190, 200, .25);
        }

        .commission-card__label,
        .metric-tile__label {
            margin: 0;
            color: rgba(255, 255, 255, .82);
            font-size: 13px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .commission-card__value {
            margin: 18px 0 10px;
            font-size: clamp(30px, 4vw, 44px);
            font-weight: 950;
            line-height: 1.05;
            letter-spacing: 0;
        }

        .commission-card__growth {
            display: inline-flex;
            width: fit-content;
            border-radius: 999px;
            background: rgba(255, 255, 255, .18);
            padding: 8px 12px;
            font-size: 14px;
            font-weight: 900;
        }

        .commission-card__footer {
            margin: 0;
            color: rgba(255, 255, 255, .84);
            font-size: 14px;
            font-weight: 900;
            letter-spacing: .03em;
        }

        .chart-card,
        .metric-tile,
        .umkm-table-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 14px 32px rgba(16, 24, 40, .06);
        }

        .chart-card {
            min-height: 258px;
            padding: 24px;
        }

        .chart-card__header,
        .table-card__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
        }

        .chart-card__title,
        .table-card__title {
            margin: 0;
            color: #151c2b;
            font-size: 22px;
            font-weight: 950;
            letter-spacing: 0;
        }

        .chart-card__subtitle {
            margin: 6px 0 0;
            color: #667085;
            font-size: 13px;
            font-weight: 800;
        }

        .date-range-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #d9e1ec;
            border-radius: 8px;
            background: #ffffff;
            color: #344054;
            padding: 10px 12px;
            font-size: 13px;
            font-weight: 900;
            white-space: nowrap;
        }

        .chart-bars {
            height: 176px;
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: minmax(12px, 1fr);
            align-items: end;
            gap: 8px;
            border-bottom: 1px solid #dbe2ec;
            margin-top: 28px;
            padding: 0 4px;
        }

        .chart-bars__bar {
            min-height: 4px;
            border-radius: 5px 5px 0 0;
            background: #11bec8;
        }

        .chart-labels {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 8px;
            margin-top: 10px;
            color: #7a8496;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .agent-metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
        }

        .metric-tile {
            min-height: 132px;
            display: grid;
            gap: 14px;
            padding: 22px;
        }

        .metric-tile__label {
            color: #667085;
        }

        .metric-tile__value {
            color: #151c2b;
            font-size: 28px;
            font-weight: 950;
            letter-spacing: 0;
        }

        .umkm-table-card {
            overflow: hidden;
        }

        .table-card__header {
            align-items: center;
            padding: 22px 24px;
        }

        .table-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-search {
            width: min(280px, 36vw);
            height: 42px;
            border: 1px solid #d9e1ec;
            border-radius: 8px;
            color: #1f2937;
            background: #ffffff;
            padding: 0 14px;
            font-size: 14px;
            font-weight: 800;
        }

        .filter-button {
            height: 42px;
            border: 1px solid #d9e1ec;
            border-radius: 8px;
            background: #ffffff;
            color: #344054;
            padding: 0 16px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 900;
        }

        .agent-table-scroll {
            overflow-x: auto;
        }

        .agent-table {
            width: 100%;
            min-width: 1040px;
            border-collapse: collapse;
        }

        .agent-table th {
            height: 48px;
            background: #f5f7fa;
            color: #667085;
            padding: 0 18px;
            text-align: left;
            font-size: 12px;
            font-weight: 950;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .agent-table td {
            height: 72px;
            border-top: 1px solid #edf1f6;
            padding: 0 18px;
            color: #17202f;
            font-size: 14px;
            font-weight: 800;
            vertical-align: middle;
        }

        .umkm-entity {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .umkm-avatar {
            width: 34px;
            height: 34px;
            display: inline-grid;
            place-items: center;
            flex: 0 0 auto;
            border-radius: 8px;
            background: #e6f8fa;
            color: #078a92;
            font-size: 12px;
            font-weight: 950;
        }

        .umkm-name {
            display: block;
            color: #151c2b;
            font-weight: 950;
        }

        .umkm-id {
            display: block;
            max-width: 180px;
            overflow: hidden;
            color: #7a8496;
            text-overflow: ellipsis;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .growth-value.is-positive { color: #079455; }
        .growth-value.is-negative { color: #d92d20; }
        .money-positive { color: #079455; font-weight: 950; }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border-radius: 999px;
            padding: 7px 11px;
            font-size: 12px;
            font-weight: 950;
            white-space: nowrap;
        }

        .status-badge::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-badge--active {
            color: #067647;
            background: #ecfdf3;
        }

        .status-badge--pending_activation {
            color: #175cd3;
            background: #eff4ff;
        }

        .detail-link,
        .detail-link[aria-disabled="true"] {
            color: #11a7b1;
            font-weight: 950;
            text-decoration: none;
            white-space: nowrap;
        }

        .detail-link[aria-disabled="true"] {
            color: #98a2b3;
            cursor: not-allowed;
        }

        .table-empty,
        .table-error {
            border-top: 1px solid #edf1f6;
            padding: 30px 24px;
            color: #667085;
            font-weight: 900;
        }

        .table-error { color: #b42318; }

        .table-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border-top: 1px solid #edf1f6;
            padding: 16px 24px;
            color: #667085;
            font-size: 14px;
            font-weight: 900;
        }

        .pagination-actions {
            display: inline-flex;
            gap: 8px;
        }

        .pagination-button {
            min-width: 38px;
            height: 36px;
            border: 1px solid #d9e1ec;
            border-radius: 8px;
            background: #ffffff;
            color: #344054;
            cursor: pointer;
            font-weight: 950;
        }

        .pagination-button:disabled {
            color: #98a2b3;
            background: #f8fafc;
            cursor: not-allowed;
        }

        .is-loading .loading-text {
            color: transparent;
            border-radius: 999px;
            background: linear-gradient(90deg, rgba(226, 232, 240, .75), rgba(248, 250, 252, .95), rgba(226, 232, 240, .75));
            background-size: 200% 100%;
            animation: shimmer 1.2s ease-in-out infinite;
        }

        @keyframes shimmer {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }

        @media (max-width: 1180px) {
            .agent-summary-grid,
            .agent-metrics {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 860px) {
            .agent-shell {
                flex-direction: column;
            }

            .agent-main {
                height: auto;
                min-height: 100vh;
                overflow: visible;
            }

            .agent-content {
                padding: 22px 18px 30px;
            }

            .agent-toolbar,
            .chart-card__header,
            .table-card__header,
            .table-pagination {
                align-items: stretch;
                flex-direction: column;
            }

            .table-actions {
                width: 100%;
                align-items: stretch;
                flex-direction: column;
            }

            .table-search,
            .filter-button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="agent-shell">
        <x-dashboard.sidebar :role="$role ?? 'agent'" :active="$active ?? 'dashboard'" />

        <main class="agent-main" aria-label="{{ $title ?? 'Dashboard Agent' }}">
            <x-dashboard.header
                :title="$headerTitle ?? 'Laporan Performa'"
                :panel-label="$panelLabel ?? 'Agent Panel'"
                :user-name="$userName ?? 'Agent'"
            />

            <div class="agent-content">
                <div class="agent-toolbar" aria-label="Periode dashboard">
                    <div class="period-control">
                        <button class="period-control__button is-active" type="button" data-period="monthly">Monthly</button>
                        <button class="period-control__button" type="button" data-period="weekly">Weekly</button>
                    </div>
                </div>

                <section class="agent-summary-grid" aria-label="Ringkasan performa agent">
                    <article class="commission-card is-loading" data-commission-card aria-busy="true">
                        <div>
                            <p class="commission-card__label">TOTAL KOMISI SAYA</p>
                            <div class="commission-card__value loading-text" data-total-commission>Rp 0</div>
                            <div class="commission-card__growth loading-text" data-commission-growth>0.0% dari periode lalu</div>
                        </div>
                        <p class="commission-card__footer">TOTAL UMKM BINAAN: <strong data-total-umkm-footer>0</strong></p>
                    </article>

                    <article class="chart-card is-loading" data-chart-card aria-busy="true">
                        <div class="chart-card__header">
                            <div>
                                <h2 class="chart-card__title">Pertumbuhan Transaksi</h2>
                                <p class="chart-card__subtitle">Aggregated volume across managed UMKM</p>
                            </div>
                            <div class="date-range-pill" data-date-range>Memuat range</div>
                        </div>
                        <div class="chart-bars" data-chart-bars aria-label="Grafik batang transaksi"></div>
                        <div class="chart-labels" data-chart-labels></div>
                    </article>
                </section>

                <section class="agent-metrics" aria-label="Metrik UMKM binaan">
                    <article class="metric-tile is-loading" data-metric="transaction-amount" aria-busy="true">
                        <p class="metric-tile__label">TOTAL TRANSAKSI UMKM BINAAN</p>
                        <div class="metric-tile__value loading-text">Rp 0</div>
                    </article>
                    <article class="metric-tile is-loading" data-metric="managed-umkm" aria-busy="true">
                        <p class="metric-tile__label">TOTAL UMKM BINAAN</p>
                        <div class="metric-tile__value loading-text">0 Toko</div>
                    </article>
                    <article class="metric-tile is-loading" data-metric="managed-areas" aria-busy="true">
                        <p class="metric-tile__label">TOTAL AREA BINAAN</p>
                        <div class="metric-tile__value loading-text">0 Area</div>
                    </article>
                </section>

                <section class="umkm-table-card" data-umkm-table-card>
                    <div class="table-card__header">
                        <h2 class="table-card__title">Performa UMKM Binaan</h2>
                        <div class="table-actions">
                            <input class="table-search" type="search" placeholder="Cari UMKM..." data-umkm-search aria-label="Cari UMKM">
                            <button class="filter-button" type="button" data-filter-button>Filter</button>
                        </div>
                    </div>
                    <div class="agent-table-scroll">
                        <table class="agent-table">
                            <thead>
                                <tr>
                                    <th>NAMA UMKM</th>
                                    <th>KATEGORI</th>
                                    <th>TOTAL TRANSAKSI</th>
                                    <th>GROWTH</th>
                                    <th>KOMISI FEE</th>
                                    <th>STATUS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody data-umkm-table-body></tbody>
                        </table>
                    </div>
                    <div class="table-empty" data-table-empty>Memuat data UMKM binaan.</div>
                    <div class="table-pagination" data-table-pagination hidden>
                        <span data-pagination-summary>Menampilkan 0 dari 0 UMKM</span>
                        <div class="pagination-actions">
                            <button class="pagination-button" type="button" data-page-prev>Prev</button>
                            <button class="pagination-button" type="button" data-page-current disabled>1</button>
                            <button class="pagination-button" type="button" data-page-next>Next</button>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        (() => {
            const token = localStorage.getItem('kresekin_token');
            const tokenType = localStorage.getItem('kresekin_token_type') || 'Bearer';
            const state = {
                period: 'monthly',
                search: '',
                page: 1,
                lastPage: 1,
            };

            if (!token) {
                window.location.replace('/');
                return;
            }

            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[char]));

            const requestJson = async (url) => {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        Authorization: `${tokenType} ${token}`,
                    },
                });

                if (response.status === 401 || response.status === 403) {
                    localStorage.removeItem('kresekin_token');
                    localStorage.removeItem('kresekin_token_type');
                    localStorage.removeItem('kresekin_user_role');
                    window.location.replace('/');
                    return null;
                }

                if (!response.ok) {
                    throw new Error('Request failed');
                }

                return response.json();
            };

            const finishLoading = (element) => {
                element?.classList.remove('is-loading');
                element?.removeAttribute('aria-busy');
            };

            const growthText = (value) => {
                const number = Number(value || 0);
                if (number === 100) return 'Baru';

                return `${number > 0 ? '+' : ''}${number.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}% dari periode lalu`;
            };

            const setMetric = (key, value) => {
                const card = document.querySelector(`[data-metric="${key}"]`);
                const target = card?.querySelector('.metric-tile__value');
                if (target) target.textContent = value || '-';
                finishLoading(card);
            };

            const renderDashboard = (data) => {
                const summary = data?.summary || {};
                const agent = data?.agent || {};
                const commission = summary.total_commission || {};
                const totalUmkm = summary.total_managed_umkm || {};

                document.querySelector('.dashboard-header__user-name').textContent = agent.name || 'Agent';
                document.querySelector('[data-total-commission]').textContent = commission.formatted || 'Rp 0';
                document.querySelector('[data-commission-growth]').textContent = growthText(commission.growth_percentage);
                document.querySelector('[data-total-umkm-footer]').textContent = totalUmkm.value ?? 0;
                finishLoading(document.querySelector('[data-commission-card]'));

                setMetric('transaction-amount', summary.total_managed_umkm_transaction_amount?.formatted || 'Rp 0');
                setMetric('managed-umkm', totalUmkm.formatted || '0 Toko');
                setMetric('managed-areas', summary.total_managed_areas?.formatted || '0 Area');
                renderChart(data?.transaction_growth || data?.transaction_trend);
            };

            const renderChart = (trend) => {
                const chart = document.querySelector('[data-chart-card]');
                const bars = document.querySelector('[data-chart-bars]');
                const labels = document.querySelector('[data-chart-labels]');
                const points = trend?.points || [];

                document.querySelector('[data-date-range]').textContent = trend?.date_range_label || '-';
                bars.innerHTML = '';
                labels.innerHTML = '';

                const max = Math.max(...points.map((point) => Number(point.transaction_count || 0)), 1);
                points.forEach((point) => {
                    const height = Math.max((Number(point.transaction_count || 0) / max) * 100, point.transaction_count > 0 ? 4 : 0);
                    bars.insertAdjacentHTML('beforeend', `<div class="chart-bars__bar" title="${escapeHtml(point.label)}: ${Number(point.transaction_count || 0)}" style="height: ${height}%"></div>`);
                });

                const labelCount = 6;
                for (let index = 0; index < labelCount; index++) {
                    const pointIndex = points.length > 1 ? Math.round(index * (points.length - 1) / (labelCount - 1)) : 0;
                    labels.insertAdjacentHTML('beforeend', `<span>${escapeHtml(points[pointIndex]?.label || '')}</span>`);
                }

                finishLoading(chart);
            };

            const renderRows = (rows) => {
                const body = document.querySelector('[data-umkm-table-body]');
                const empty = document.querySelector('[data-table-empty]');
                body.innerHTML = '';

                if (!rows.length) {
                    empty.className = 'table-empty';
                    empty.textContent = 'Data tidak ditemukan';
                    empty.hidden = false;
                    return;
                }

                empty.hidden = true;
                body.innerHTML = rows.map((row) => {
                    const growthClass = Number(row.growth_percentage || 0) < 0 ? 'is-negative' : 'is-positive';
                    const action = row.detail_url
                        ? `<a class="detail-link" href="${escapeHtml(row.detail_url)}">View Details</a>`
                        : '<span class="detail-link" aria-disabled="true">View Details</span>';

                    return `
                        <tr>
                            <td>
                                <span class="umkm-entity">
                                    <span class="umkm-avatar">${escapeHtml(row.initials || '?')}</span>
                                    <span>
                                        <span class="umkm-name">${escapeHtml(row.name || '-')}</span>
                                        <span class="umkm-id">${escapeHtml(row.display_id || row.id || '-')}</span>
                                    </span>
                                </span>
                            </td>
                            <td>${escapeHtml(row.category || '-')}</td>
                            <td>${escapeHtml(row.total_transaction_amount_label || 'Rp 0')}</td>
                            <td><span class="growth-value ${growthClass}">${escapeHtml(row.growth_label || '0.0%')}</span></td>
                            <td><span class="money-positive">${escapeHtml(row.agent_commission_label || 'Rp 0')}</span></td>
                            <td><span class="status-badge status-badge--${escapeHtml(row.status || 'pending_activation')}">${escapeHtml(row.status_label || '-')}</span></td>
                            <td>${action}</td>
                        </tr>
                    `;
                }).join('');
            };

            const renderPagination = (meta) => {
                const pagination = document.querySelector('[data-table-pagination]');
                const from = meta?.from ?? 0;
                const to = meta?.to ?? 0;
                const total = meta?.total ?? 0;
                state.page = meta?.current_page || 1;
                state.lastPage = meta?.last_page || 1;

                document.querySelector('[data-pagination-summary]').textContent = `Menampilkan ${from}-${to} dari ${total} UMKM`;
                document.querySelector('[data-page-current]').textContent = state.page;
                document.querySelector('[data-page-prev]').disabled = state.page <= 1;
                document.querySelector('[data-page-next]').disabled = state.page >= state.lastPage;
                pagination.hidden = false;
            };

            const loadDashboard = async () => {
                const payload = await requestJson(`/api/agent/dashboard?period=${encodeURIComponent(state.period)}`);
                if (payload?.data) renderDashboard(payload.data);
            };

            const loadUmkm = async () => {
                const params = new URLSearchParams({
                    period: state.period,
                    search: state.search,
                    page: state.page,
                    per_page: 10,
                });
                const empty = document.querySelector('[data-table-empty]');
                empty.className = 'table-empty';
                empty.textContent = 'Memuat data UMKM binaan.';
                empty.hidden = false;

                const payload = await requestJson(`/api/agent/managed-umkm?${params.toString()}`);
                renderRows(payload?.data || []);
                renderPagination(payload?.meta || {});
            };

            const reloadAll = () => {
                loadDashboard().catch(() => {
                    document.querySelector('[data-total-commission]').textContent = 'Rp 0';
                    document.querySelector('[data-commission-growth]').textContent = 'Gagal memuat ringkasan';
                    document.querySelectorAll('.is-loading').forEach(finishLoading);
                });
                loadUmkm().catch(() => {
                    const empty = document.querySelector('[data-table-empty]');
                    empty.className = 'table-error';
                    empty.textContent = 'Gagal memuat data UMKM binaan.';
                    empty.hidden = false;
                });
            };

            document.querySelectorAll('[data-period]').forEach((button) => {
                button.addEventListener('click', () => {
                    state.period = button.dataset.period;
                    state.page = 1;
                    document.querySelectorAll('[data-period]').forEach((item) => item.classList.toggle('is-active', item === button));
                    reloadAll();
                });
            });

            let searchTimer;
            document.querySelector('[data-umkm-search]').addEventListener('input', (event) => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                    state.search = event.target.value.trim();
                    state.page = 1;
                    loadUmkm().catch(() => {
                        const empty = document.querySelector('[data-table-empty]');
                        empty.className = 'table-error';
                        empty.textContent = 'Gagal memuat data UMKM binaan.';
                        empty.hidden = false;
                    });
                }, 300);
            });

            document.querySelector('[data-filter-button]').addEventListener('click', () => {
                state.page = 1;
                loadUmkm();
            });

            document.querySelector('[data-page-prev]').addEventListener('click', () => {
                if (state.page > 1) {
                    state.page -= 1;
                    loadUmkm();
                }
            });

            document.querySelector('[data-page-next]').addEventListener('click', () => {
                if (state.page < state.lastPage) {
                    state.page += 1;
                    loadUmkm();
                }
            });

            reloadAll();
        })();
    </script>
</body>
</html>
