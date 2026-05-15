@extends('agent.layout', ['title' => 'Agent Dashboard'])

@section('content')
@php
    $money = fn (int $amount): string => 'Rp. ' . number_format($amount, 0, ',', '.');
@endphp

<div class="mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1 class="h3 mb-1">Agent Dashboard</h1>
            <div class="text-secondary">Halo, {{ $agentName }}. Ringkasan transaksi, seller, dan komisi agent.</div>
            <div class="text-secondary small mt-1">{{ $agentEmail }}</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-success" href="{{ route('agent.sellers.index') }}">Pantau Seller</a>
            <a class="btn btn-success" href="{{ route('agent.withdrawals.index') }}">Cairkan Komisi</a>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-3">
        <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
                <div class="text-secondary">Total Revenue</div>
                <div class="h3 fw-bold">{{ $money($summary['total_revenue']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
                <div class="text-secondary">Komisi Agent</div>
                <div class="h3 fw-bold">{{ $money($summary['total_commission']) }}</div>
                <div class="small text-secondary">Rate {{ number_format($summary['commission_rate'] * 100, 2) }}%</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
                <div class="text-secondary">Saldo Tersedia</div>
                <div class="h3 fw-bold">{{ $money($summary['available_commission']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
                <div class="text-secondary">Seller / Tenant</div>
                <div class="h3 fw-bold">{{ $sellerCount }} / {{ $tenantCount }}</div>
                <div class="small text-secondary">{{ $transactionCount }} transaksi, {{ $productCount }} produk</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm my-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">Tabel Toko</h2>
                <div class="text-secondary small">Nama toko, jumlah transaksi, total revenue, dan komisi agent.</div>
            </div>
            <a class="btn btn-outline-success btn-sm" href="{{ route('agent.tenants.index') }}">Kelola Tenant</a>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr class="table-light">
                        <th>Nama Toko</th>
                        <th>Seller</th>
                        <th>Jumlah Transaksi</th>
                        <th>Total Revenue</th>
                        <th>Komisi Agent</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stores as $store)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $store['tenant']->name }}</div>
                                <div class="text-secondary small">{{ $store['tenant']->category }}</div>
                            </td>
                            <td>{{ $store['tenant']->owner?->name ?? '-' }}</td>
                            <td>{{ $store['transaction_count'] }}</td>
                            <td>{{ $money($store['revenue']) }}</td>
                            <td>{{ $money($store['commission']) }}</td>
                            <td>
                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('agent.tenants.show', $store['tenant']->id) }}">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-secondary">Belum ada toko dalam lingkup agent.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Transaksi Terbaru</h2>
                <div class="vstack gap-2">
                    @forelse ($recentTransactions as $transaction)
                        <div class="border rounded-3 p-3">
                            <div class="d-flex justify-content-between gap-3">
                                <div>
                                    <div class="fw-semibold">{{ $transaction->order_number }}</div>
                                    <div class="text-secondary small">{{ $transaction->items->first()?->tenant?->name ?? '-' }} &middot; {{ $transaction->user?->name ?? 'Buyer' }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold">{{ $money((int) $transaction->total_amount) }}</div>
                                    <div class="text-secondary small">{{ $transaction->status }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-secondary">Belum ada transaksi.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h5 mb-0">Tenant Terbaru</h2>
                    <a class="btn btn-success btn-sm" href="{{ route('agent.tenants.create') }}">Tambah</a>
                </div>
                <div class="vstack gap-2">
                    @forelse ($recentTenants as $tenant)
                        <div class="border rounded-3 p-3">
                            <div class="fw-semibold">{{ $tenant->name ?? 'Tenant #' . $tenant->id }}</div>
                            <div class="text-secondary small">ID: {{ $tenant->id }}</div>
                        </div>
                    @empty
                        <div class="text-secondary">Belum ada tenant.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
