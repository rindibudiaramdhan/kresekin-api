@extends('finance.layout', ['title' => 'Finance Management'])

@section('content')
@php
    $transactions = [
        [
            'id' => 'WD-20230914-0042',
            'agent' => 'Budi Sentosa',
            'bank' => 'BCA - 8830129xxx',
            'nominal' => 'Rp 5.200.456',
            'date' => '2 Jan 2026',
            'status' => 'Berhasil',
            'statusClass' => 'status-success',
        ],
        [
            'id' => 'WD-20230914-0042',
            'agent' => 'Santi',
            'bank' => 'Mandiri - 1240098xxx',
            'nominal' => 'Rp 2.450.999',
            'date' => '15 Feb 2026',
            'status' => 'Pengajuan',
            'statusClass' => 'status-info',
        ],
        [
            'id' => 'WD-20230914-0042',
            'agent' => 'Asep',
            'bank' => 'BNI - 098122xxx',
            'nominal' => 'Rp 875.056',
            'date' => '6 Mar 2026',
            'status' => 'Diproses',
            'statusClass' => 'status-process',
        ],
        [
            'id' => 'WD-20230914-0042',
            'agent' => 'Denny',
            'bank' => 'BSI - 012322xxx',
            'nominal' => 'Rp 1.025.873',
            'date' => '6 Mar 2026',
            'status' => 'Ditolak',
            'statusClass' => 'status-danger',
        ],
    ];
@endphp

@push('styles')
<style>
    .finance-page-title {
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: 0;
        line-height: 1.2;
    }

    .finance-page-description {
        color: #4d5362;
        font-size: 1.08rem;
    }

    .filter-control {
        align-items: center;
        background: #fff;
        border: 1px solid #c9d2e3;
        border-radius: 8px;
        color: #53617a;
        display: flex;
        gap: .75rem;
        min-height: 46px;
        padding: 0 .9rem;
    }

    .filter-control input {
        border: 0;
        color: #202634;
        min-width: 0;
        outline: 0;
        width: 100%;
    }

    .filter-control input::placeholder {
        color: #9ba3af;
        opacity: 1;
    }

    .filter-select,
    .filter-date {
        min-width: 180px;
    }

    .tab-button {
        align-items: center;
        background: transparent;
        border: 0;
        color: #555c68;
        display: inline-flex;
        font-weight: 800;
        gap: .55rem;
        min-height: 64px;
        padding: 0 1.6rem;
        position: relative;
    }

    .tab-button.active {
        color: var(--finance-teal);
    }

    .tab-button.active::after {
        background: var(--finance-teal);
        bottom: 0;
        content: "";
        height: 2px;
        left: 1.6rem;
        position: absolute;
        right: 1.6rem;
    }

    .transaction-id {
        color: #454c5d;
        font-weight: 800;
        letter-spacing: .04em;
        line-height: 1.1;
    }

    .pager-button {
        align-items: center;
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
        color: #111827;
        display: inline-flex;
        font-weight: 800;
        height: 44px;
        justify-content: center;
        min-width: 44px;
        padding: 0 .8rem;
        text-decoration: none;
    }

    .pager-button.active {
        background: var(--finance-teal);
        border-color: var(--finance-teal);
        color: #fff;
    }

    @media (max-width: 767.98px) {
        .metric-value {
            font-size: 1.55rem;
        }

        .filter-select,
        .filter-date {
            min-width: 100%;
        }
    }
</style>
@endpush

<section class="mb-4">
    <h1 class="finance-page-title mb-1">Finance Management</h1>
    <div class="finance-page-description">Meninjau dan mengelola aktivitas dan persetujuan keuangan</div>
</section>

<section class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="finance-card h-100 p-4">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div>
                    <div class="metric-label mb-2">Total Dana Tersalurkan</div>
                    <div class="metric-value">Rp 45.000.000</div>
                </div>
                <span class="soft-icon success">
                    <svg aria-hidden="true" fill="none" height="24" viewBox="0 0 24 24" width="24">
                        <path d="M7.5 12.5 10.5 15.5 16.5 8.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"/>
                        <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </span>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="finance-card h-100 p-4">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div>
                    <div class="metric-label mb-2">Total Dana Tertunda</div>
                    <div class="metric-value">Rp 45.000.000</div>
                </div>
                <span class="soft-icon primary">
                    <svg aria-hidden="true" fill="none" height="24" viewBox="0 0 24 24" width="24">
                        <path d="M5 7.5h14a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-11a2 2 0 0 1 2-2h12" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                        <path d="M16 13h.01" stroke="currentColor" stroke-linecap="round" stroke-width="3"/>
                    </svg>
                </span>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="finance-card h-100 p-4">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div>
                    <div class="metric-label mb-2">Jumlah Pencairan Komisi</div>
                    <div class="metric-value">249</div>
                </div>
                <span class="soft-icon warning">
                    <svg aria-hidden="true" fill="none" height="24" viewBox="0 0 24 24" width="24">
                        <path d="M9 5h6M9 5a3 3 0 0 0-3 3v10h12V8a3 3 0 0 0-3-3M9 5V3h6v2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                        <path d="M17 15.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm0 1.75v1.95l1.2.8" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                    </svg>
                </span>
            </div>
        </div>
    </div>
</section>

<section class="finance-card p-3 p-lg-4 mb-4">
    <form class="d-flex flex-wrap align-items-center gap-3" method="GET" action="{{ route('finance.dashboard') }}">
        <label class="filter-control flex-grow-1" style="min-width: 260px;">
            <svg aria-hidden="true" fill="none" height="20" viewBox="0 0 24 24" width="20">
                <path d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM21 21l-4.35-4.35" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
            </svg>
            <input aria-label="Cari nama atau ID agent" name="q" placeholder="Cari Nama atau ID Agent..." type="search">
        </label>
        <div class="filter-control filter-select">
            <span>Semua Status</span>
            <span class="ms-auto">⌄</span>
        </div>
        <div class="filter-control filter-date">
            <svg aria-hidden="true" fill="none" height="20" viewBox="0 0 24 24" width="20">
                <path d="M8 2v4M16 2v4M3.5 9h17M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
            </svg>
            <span>Oct 1 - Oct 31, 2026</span>
        </div>
        <button class="btn btn-teal px-4" type="submit">Terapkan Filter</button>
    </form>
</section>

<section class="finance-card overflow-hidden">
    <div class="d-flex align-items-center border-bottom">
        <button class="tab-button" type="button">
            <span>▤</span>
            Transaksi Seller
        </button>
        <button class="tab-button active" type="button">
            <span>▧</span>
            Transaksi Agent
        </button>
    </div>

    <div class="table-responsive">
        <table class="table finance-table align-middle">
            <thead>
                <tr>
                    <th>ID Transaksi</th>
                    <th>Nama Agent</th>
                    <th>Bank Tujuan</th>
                    <th>Nominal</th>
                    <th>Tanggal Pengajuan</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transactions as $transaction)
                    <tr>
                        <td>
                            <div class="transaction-id">{{ \Illuminate\Support\Str::beforeLast($transaction['id'], '-') }}-</div>
                            <div class="transaction-id">{{ \Illuminate\Support\Str::afterLast($transaction['id'], '-') }}</div>
                        </td>
                        <td>{{ $transaction['agent'] }}</td>
                        <td>
                            <div>{{ \Illuminate\Support\Str::before($transaction['bank'], ' -') }} -</div>
                            <div>{{ \Illuminate\Support\Str::after($transaction['bank'], '- ') }}</div>
                        </td>
                        <td class="fw-semibold">{{ $transaction['nominal'] }}</td>
                        <td>{{ $transaction['date'] }}</td>
                        <td>
                            <span class="status-pill {{ $transaction['statusClass'] }}">{{ $transaction['status'] }}</span>
                        </td>
                        <td>
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('finance.transactions.index') }}">Detail</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 border-top px-4 py-5">
        <div class="text-secondary">Menampilkan 1-5 dari 24 Transaksi Pencairan</div>
        <nav class="d-flex align-items-center gap-2" aria-label="Pagination transaksi pencairan">
            <a class="pager-button text-secondary" href="#" aria-label="Halaman sebelumnya">‹</a>
            <a class="pager-button active" href="#">1</a>
            <a class="pager-button" href="#">2</a>
            <a class="pager-button" href="#">3</a>
            <span class="px-2">...</span>
            <a class="pager-button" href="#">5</a>
            <a class="pager-button" href="#" aria-label="Halaman berikutnya">›</a>
        </nav>
    </div>
</section>
@endsection
