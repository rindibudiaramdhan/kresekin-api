<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $title ?? 'Dashboard' }} - {{ config('app.name', 'Kresek.in') }}</title>
    @php
        use Illuminate\Support\HtmlString;

        $rupiah = fn (int $amount): string => 'Rp '.number_format($amount, 0, ',', '.');
        $plainRupiah = fn (float $amount): string => 'Rp '.number_format($amount, 2, '.', ',');
        $cell = fn (string $html): HtmlString => new HtmlString($html);

        $spotlightItems = [
            ['initials' => 'T', 'name' => 'Tokoma', 'category' => 'Sayur', 'growth' => 240],
            ['initials' => 'ST', 'name' => 'Sembako Tetang...', 'category' => 'Retail', 'growth' => 185],
        ];

        $transactionColumns = [
            ['key' => 'order_number', 'label' => 'No Pesanan'],
            ['key' => 'date', 'label' => 'Tanggal Transaksi'],
            ['key' => 'umkm', 'label' => 'Nama UMKM'],
            ['key' => 'total', 'label' => 'Total Transaksi'],
            ['key' => 'status', 'label' => 'Status'],
        ];

        $transactionRows = [
            [
                'order_number' => '#26032301CATSYR',
                'date' => $cell('<span class="data-table__muted">May 30, 2024</span>'),
                'umkm' => $cell('<span class="data-table__entity">'.view('components.dashboard.avatar-initial', ['initials' => 'KN'])->render().'Kopi Nusantara</span>'),
                'total' => $plainRupiah(1240),
                'status' => $cell(view('components.dashboard.status-badge', ['status' => 'success', 'label' => 'Success'])->render()),
            ],
            [
                'order_number' => '#26032301CATSYR',
                'date' => $cell('<span class="data-table__muted">May 30, 2024</span>'),
                'umkm' => $cell('<span class="data-table__entity">'.view('components.dashboard.avatar-initial', ['initials' => 'BM', 'tone' => 'soft'])->render().'Batik Moderno</span>'),
                'total' => $plainRupiah(850.5),
                'status' => $cell(view('components.dashboard.status-badge', ['status' => 'pending', 'label' => 'Pending'])->render()),
            ],
            [
                'order_number' => '#26032301CATSYR',
                'date' => $cell('<span class="data-table__muted">May 29, 2024</span>'),
                'umkm' => $cell('<span class="data-table__entity">'.view('components.dashboard.avatar-initial', ['initials' => 'SJ', 'tone' => 'red'])->render().'Soto Jaya</span>'),
                'total' => $plainRupiah(45),
                'status' => $cell(view('components.dashboard.status-badge', ['status' => 'failed', 'label' => 'Failed'])->render()),
            ],
        ];

        $commissionColumns = [
            ['key' => 'agent', 'label' => 'Nama Agent'],
            ['key' => 'managed', 'label' => 'Managed UMKM'],
            ['key' => 'revenue', 'label' => 'Pendapatan Toko'],
            ['key' => 'commission', 'label' => 'Komisi Agent'],
            ['key' => 'status', 'label' => 'Status'],
        ];

        $commissionRows = [
            [
                'agent' => 'Agent XYZ',
                'managed' => '42 Toko',
                'revenue' => $rupiah(285000),
                'commission' => $cell('<span class="dashboard-money-positive">'.$rupiah(142500).'</span>'),
                'status' => $cell(view('components.dashboard.status-badge', ['status' => 'approved', 'label' => 'BERHASIL'])->render()),
            ],
            [
                'agent' => 'Denny Caknan',
                'managed' => '38 Toko',
                'revenue' => $rupiah(242000),
                'commission' => $cell('<span class="dashboard-money-positive">'.$rupiah(12100).'</span>'),
                'status' => $cell(view('components.dashboard.status-badge', ['status' => 'processing', 'label' => 'DIPROSES'])->render()),
            ],
            [
                'agent' => 'Lukas Pesek',
                'managed' => '31 Toko',
                'revenue' => $rupiah(198500),
                'commission' => $cell('<span class="dashboard-money-positive">'.$rupiah(992500).'</span>'),
                'status' => $cell(view('components.dashboard.status-badge', ['status' => 'rejected', 'label' => 'DITOLAK'])->render()),
            ],
        ];
    @endphp
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
                        title="Total Pendapatan UMKM"
                        value="Rp 4,280,500,000"
                        icon="money"
                        growth="12.5"
                    />
                    <x-dashboard.metric-card
                        title="Total Pesanan"
                        value="12,845"
                        icon="cart"
                        growth="8.2"
                    />
                    <x-dashboard.metric-card
                        title="Total UMKM Aktif"
                        value="1,240"
                        icon="store"
                        tone="green"
                        caption="Active Partners"
                    />
                </section>

                <section class="dashboard-feature-grid">
                    <x-dashboard.trend-chart-card title="Tren Transaksi UMKM" />
                    <x-dashboard.spotlight-card
                        title="UMKM Spotlight"
                        subtitle="UMKM dengan pertumbuhan terbaik"
                        :items="$spotlightItems"
                    />
                </section>

                <x-dashboard.data-table
                    title="Transaksi Terbaru Top 3"
                    :columns="$transactionColumns"
                    :rows="$transactionRows"
                />

                <section class="dashboard-bottom-grid">
                    <x-dashboard.data-table
                        title="Top Agent Commissions"
                        :columns="$commissionColumns"
                        :rows="$commissionRows"
                    />
                    <x-dashboard.summary-highlight-card
                        label="Agent Fees & Commissions"
                        value="Rp 482,500,000"
                        footer-label="Total Agent"
                        footer-value="842"
                    />
                </section>
            </div>
        </main>
    </div>
</body>
</html>
