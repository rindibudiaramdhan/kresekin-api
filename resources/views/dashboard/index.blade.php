<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $title ?? 'Dashboard' }} - {{ config('app.name', 'Kresek.in') }}</title>
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

        .dashboard-content {
            display: grid;
            gap: 28px;
            padding: 28px 34px;
        }

        .dashboard-metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 28px;
        }

        .dashboard-feature-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
            gap: 28px;
            align-items: stretch;
        }

        .dashboard-bottom-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
            gap: 28px;
            align-items: start;
        }

        .dashboard-money-positive {
            color: #06923c;
            font-weight: 900;
        }

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

        @keyframes dashboard-loading-shimmer {
            0% {
                background-position: 100% 0;
            }

            100% {
                background-position: -100% 0;
            }
        }

        .dashboard-loading-line,
        .metric-card.is-loading .metric-card__value,
        .metric-card.is-loading .metric-card__growth,
        .summary-highlight-card.is-loading .summary-highlight-card__value,
        .summary-highlight-card.is-loading .summary-highlight-card__footer strong {
            color: transparent;
            border-radius: 999px;
            background: linear-gradient(90deg, rgba(226, 232, 240, .7), rgba(248, 250, 252, .95), rgba(226, 232, 240, .7));
            background-size: 200% 100%;
            animation: dashboard-loading-shimmer 1.2s ease-in-out infinite;
        }

        .dashboard-loading-line {
            width: 100%;
            height: 14px;
            display: block;
            background: linear-gradient(90deg, rgba(255, 255, 255, .14), rgba(255, 255, 255, .32), rgba(255, 255, 255, .14));
            background-size: 200% 100%;
        }

        .dashboard-loading-line--short {
            width: 64%;
        }

        .dashboard-loading-line--tiny {
            width: 54px;
            height: 16px;
        }

        .metric-card.is-loading .metric-card__value {
            width: min(280px, 82%);
            min-height: 42px;
        }

        .metric-card.is-loading .metric-card__growth {
            width: 82px;
            min-height: 18px;
        }

        .trend-card.is-loading .trend-card__line {
            stroke-dasharray: 14 12;
            animation: dashboard-loading-shimmer 1.2s linear infinite;
        }

        .trend-card.is-loading .trend-card__area {
            opacity: .5;
        }

        .data-table-card.is-loading .data-table__empty {
            position: relative;
            min-height: 116px;
            color: transparent;
        }

        .data-table-card.is-loading .data-table__empty::before,
        .data-table-card.is-loading .data-table__empty::after {
            content: "";
            position: absolute;
            left: 28px;
            right: 28px;
            height: 18px;
            border-radius: 999px;
            background: linear-gradient(90deg, #e2e8f0, #f8fafc, #e2e8f0);
            background-size: 200% 100%;
            animation: dashboard-loading-shimmer 1.2s ease-in-out infinite;
        }

        .data-table-card.is-loading .data-table__empty::before {
            top: 32px;
        }

        .data-table-card.is-loading .data-table__empty::after {
            top: 68px;
            width: 62%;
        }

        .summary-highlight-card.is-loading .summary-highlight-card__value {
            width: min(340px, 86%);
            min-height: 44px;
        }

        .summary-highlight-card.is-loading .summary-highlight-card__footer strong {
            display: inline-block;
            width: 52px;
            min-height: 20px;
            vertical-align: middle;
        }

        @media (max-width: 1180px) {
            .dashboard-metrics,
            .dashboard-feature-grid,
            .dashboard-bottom-grid {
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

            .dashboard-content {
                padding: 22px 18px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-shell">
        <x-dashboard.sidebar :role="$role ?? 'agent'" :active="$active ?? 'dashboard'" />

        <main class="dashboard-main" aria-label="{{ $title ?? 'Dashboard' }}">
            <x-dashboard.header :title="$headerTitle ?? 'Admin Views'" :user-name="$userName ?? 'System Administrator'" />

            <div class="dashboard-content">
                <section class="dashboard-metrics" aria-label="Ringkasan dashboard">
                    <x-dashboard.metric-card
                        class="is-loading"
                        aria-busy="true"
                        data-dashboard-metric="revenue"
                        title="Total Pendapatan UMKM"
                        value="-"
                        icon="money"
                        growth=""
                    />
                    <x-dashboard.metric-card
                        class="is-loading"
                        aria-busy="true"
                        data-dashboard-metric="orders"
                        title="Total Pesanan"
                        value="-"
                        icon="cart"
                        growth=""
                    />
                    <x-dashboard.metric-card
                        class="is-loading"
                        aria-busy="true"
                        data-dashboard-metric="active-umkm"
                        title="Total UMKM Aktif"
                        value="-"
                        icon="store"
                        tone="green"
                        caption="Active Partners"
                    />
                </section>

                <section class="dashboard-feature-grid">
                    <x-dashboard.trend-chart-card class="is-loading" aria-busy="true" data-dashboard-trend title="Tren Transaksi UMKM" />
                    <x-dashboard.spotlight-card
                        class="is-loading"
                        aria-busy="true"
                        data-dashboard-spotlight
                        title="UMKM Spotlight"
                        subtitle="UMKM dengan pertumbuhan terbaik"
                        :items="[]"
                    />
                </section>

                <x-dashboard.data-table
                    class="is-loading"
                    aria-busy="true"
                    data-dashboard-table="transactions"
                    title="Transaksi Terbaru Top 3"
                    :columns="[]"
                    :rows="[]"
                    empty-message="Memuat data transaksi."
                />

                <section class="dashboard-bottom-grid">
                    <x-dashboard.data-table
                        class="is-loading"
                        aria-busy="true"
                        data-dashboard-table="commissions"
                        title="Top Agent Commissions"
                        :columns="[]"
                        :rows="[]"
                        empty-message="Memuat data komisi."
                    />
                    <x-dashboard.summary-highlight-card
                        class="is-loading"
                        aria-busy="true"
                        data-dashboard-summary="commission"
                        label="Agent Fees & Commissions"
                        value="-"
                        footer-label="Total Agent"
                        footer-value="-"
                    />
                </section>
            </div>
        </main>
    </div>
    <script>
        (() => {
            const role = @json($role ?? 'agent');
            const apiUrl = `/api/${role}/dashboard`;
            const token = localStorage.getItem('kresekin_token');
            const tokenType = localStorage.getItem('kresekin_token_type') || 'Bearer';

            if (!token) {
                window.location.replace('/');
                return;
            }

            const money = (value) => `Rp ${Number(value || 0).toLocaleString('id-ID')}`;
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[char]));
            const statusClass = (status) => {
                if (['success', 'completed'].includes(status)) return 'success';
                if (['failed', 'canceled'].includes(status)) return 'failed';
                if (['approved', 'processing', 'rejected', 'estimated'].includes(status)) return status;
                return 'pending';
            };
            const badge = (status, label) => `<span class="status-badge status-badge--${statusClass(status)}">${escapeHtml(label)}</span>`;
            const initials = (value) => escapeHtml(value || '?');
            const finishLoading = (element) => {
                element?.classList.remove('is-loading');
                element?.removeAttribute('aria-busy');
            };
            const entity = (item) => {
                const umkm = item?.umkm || {};
                return `<span class="data-table__entity"><span class="avatar-initial">${initials(umkm.initials)}</span>${escapeHtml(umkm.name || item?.store_name || '-')}</span>`;
            };

            const renderMetric = (key, payload) => {
                const el = document.querySelector(`[data-dashboard-metric="${key}"]`);
                if (!el) return;

                const value = el.querySelector('.metric-card__value');
                const growth = el.querySelector('.metric-card__growth');
                const caption = el.querySelector('.metric-card__caption');

                if (value) value.textContent = payload?.formatted ?? payload?.value ?? '-';
                if (growth && payload?.growth_percentage !== undefined) {
                    const rate = Number(payload.growth_percentage || 0);
                    growth.textContent = `${rate >= 0 ? '↗ +' : '↘ '}${rate}%`;
                }
                if (caption && payload?.caption) caption.textContent = payload.caption;

                finishLoading(el);
            };

            const renderTrend = (trend) => {
                const chart = document.querySelector('[data-dashboard-trend]');
                if (!chart) return;
                if (!trend?.points?.length) {
                    finishLoading(chart);
                    return;
                }

                const points = trend.points;
                const max = Math.max(...points.map((point) => Number(point.revenue || point.transaction_count || 0)), 1);
                const startX = 22;
                const endX = 752;
                const topY = 56;
                const baseY = 278;
                const step = points.length > 1 ? (endX - startX) / (points.length - 1) : 0;
                const coords = points.map((point, index) => {
                    const value = Number(point.revenue || point.transaction_count || 0);
                    const x = startX + (step * index);
                    const y = baseY - ((value / max) * (baseY - topY));
                    return [Number(x.toFixed(1)), Number(y.toFixed(1))];
                });
                const linePath = coords.map(([x, y], index) => `${index === 0 ? 'M' : 'L'}${x} ${y}`).join(' ');
                const areaPath = `${linePath} L${endX} ${baseY} L${startX} ${baseY} Z`;

                chart.querySelector('.trend-card__line')?.setAttribute('d', linePath);
                chart.querySelector('.trend-card__area')?.setAttribute('d', areaPath);

                const labels = chart.querySelectorAll('.trend-card__axis');
                labels.forEach((label, index) => {
                    const pointIndex = Math.min(points.length - 1, Math.round(index * (points.length - 1) / Math.max(labels.length - 1, 1)));
                    label.textContent = (points[pointIndex]?.label || '').toUpperCase();
                });

                finishLoading(chart);
            };

            const renderSpotlight = (items) => {
                const section = document.querySelector('[data-dashboard-spotlight]');
                const card = section?.querySelector('.spotlight-card__list');
                if (!card) return;

                if (!items?.length) {
                    card.innerHTML = '<div class="spotlight-card__item">Belum ada data pertumbuhan.</div>';
                    finishLoading(section);
                    return;
                }

                card.innerHTML = items.map((item) => `
                    <div class="spotlight-card__item">
                        <span class="avatar-initial avatar-initial--cyan">${initials(item.initials)}</span>
                        <div>
                            <div class="spotlight-card__name">${escapeHtml(item.name)}</div>
                            <div class="spotlight-card__category">${escapeHtml(item.category || '-')}</div>
                        </div>
                        <div class="spotlight-card__growth">+${Number(item.growth_percentage || 0)}%</div>
                    </div>
                `).join('');

                finishLoading(section);
            };

            const renderTable = (key, columns, rows, emptyMessage) => {
                const table = document.querySelector(`[data-dashboard-table="${key}"]`);
                if (!table) return;

                if (!rows.length) {
                    table.querySelector('.data-table-card__scroll')?.remove();
                    let empty = table.querySelector('.data-table__empty');
                    if (!empty) {
                        empty = document.createElement('div');
                        empty.className = 'data-table__empty';
                        table.appendChild(empty);
                    }
                    empty.textContent = emptyMessage;
                    finishLoading(table);
                    return;
                }

                table.querySelector('.data-table__empty')?.remove();
                table.querySelector('.data-table-card__scroll')?.remove();
                table.insertAdjacentHTML('beforeend', `
                    <div class="data-table-card__scroll">
                        <table class="data-table">
                            <thead><tr>${columns.map((column) => `<th>${escapeHtml(column.label)}</th>`).join('')}</tr></thead>
                            <tbody>
                                ${rows.map((row) => `<tr>${columns.map((column) => `<td>${column.render(row)}</td>`).join('')}</tr>`).join('')}
                            </tbody>
                        </table>
                    </div>
                `);
                finishLoading(table);
            };

            const renderSummaryCard = (summary) => {
                const card = document.querySelector('[data-dashboard-summary="commission"]');
                if (!card) return;

                card.querySelector('.summary-highlight-card__value').textContent = summary?.total_agent_commission_formatted ?? money(summary?.total_agent_commission);
                card.querySelector('.summary-highlight-card__footer strong').textContent = summary?.total_agents_formatted ?? summary?.total_agents ?? '0';
                finishLoading(card);
            };

            fetch(apiUrl, {
                headers: {
                    Accept: 'application/json',
                    Authorization: `${tokenType} ${token}`,
                },
            })
                .then(async (response) => {
                    if (response.status === 401 || response.status === 403) {
                        localStorage.removeItem('kresekin_token');
                        localStorage.removeItem('kresekin_token_type');
                        localStorage.removeItem('kresekin_user_role');
                        window.location.replace('/');
                        return null;
                    }

                    if (!response.ok) throw new Error('Dashboard request failed');

                    return response.json();
                })
                .then((payload) => {
                    const data = payload?.data;
                    if (!data) return;

                    renderMetric('revenue', data.summary?.total_umkm_revenue);
                    renderMetric('orders', data.summary?.total_orders);
                    renderMetric('active-umkm', data.summary?.active_umkm);
                    renderTrend(data.transaction_trend);
                    renderSpotlight(data.umkm_spotlight);
                    renderSummaryCard(data.commission_summary);

                    renderTable('transactions', [
                        { label: 'No Pesanan', render: (row) => escapeHtml(row.order_number) },
                        { label: 'Tanggal Transaksi', render: (row) => `<span class="data-table__muted">${escapeHtml(row.transaction_date_label || '-')}</span>` },
                        { label: 'Nama UMKM', render: entity },
                        { label: 'Total Transaksi', render: (row) => escapeHtml(row.total_amount_formatted || row.total_amount_label || money(row.total_amount)) },
                        { label: 'Status', render: (row) => badge(row.status_label?.toLowerCase(), row.status_label || 'Pending') },
                    ], (data.recent_transactions || []).slice(0, 3), 'Belum ada transaksi.');

                    const commissionRows = role === 'finance' ? (data.top_agent_commissions || []) : (data.top_umkm_commissions || []);
                    renderTable('commissions', [
                        { label: role === 'finance' ? 'Nama Agent' : 'Nama UMKM', render: (row) => escapeHtml(row.agent?.name || row.umkm?.name || '-') },
                        { label: 'Managed UMKM', render: (row) => escapeHtml(row.managed_umkm_label || '-') },
                        { label: 'Pendapatan Toko', render: (row) => escapeHtml(row.store_revenue_formatted || money(row.store_revenue)) },
                        { label: 'Komisi Agent', render: (row) => `<span class="dashboard-money-positive">${escapeHtml(row.agent_commission_formatted || money(row.agent_commission))}</span>` },
                        { label: 'Status', render: (row) => row.status ? badge(row.status, row.status_label || row.status) : '-' },
                    ], commissionRows, role === 'finance' ? 'Belum ada data komisi agent.' : 'Belum ada data komisi UMKM.');
                })
                .catch(() => {
                    renderTable('transactions', [], [], 'Gagal memuat data dashboard.');
                    renderTable('commissions', [], [], 'Gagal memuat data komisi.');
                    document.querySelectorAll('.is-loading').forEach(finishLoading);
                });
        })();
    </script>
</body>
</html>
