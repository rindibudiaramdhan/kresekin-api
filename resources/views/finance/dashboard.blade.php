@extends('finance.layout', ['title' => 'Finance Dashboard'])

@section('content')
@php
    $money = fn (int $amount): string => 'Rp. ' . number_format($amount, 0, ',', '.');
@endphp

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Dashboard Finance</h1>
        <div class="text-secondary">Ringkasan transaksi semua toko dan status toko aktif.</div>
    </div>
    <a class="btn btn-success" href="{{ route('finance.transactions.index') }}">Kelola Transaksi</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
                <div class="text-secondary">Total Transaksi</div>
                <div class="h3 fw-bold">{{ $totalTransactions }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
                <div class="text-secondary">Total Nominal</div>
                <div class="h3 fw-bold">{{ $money($totalTransactionAmount) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
                <div class="text-secondary">Toko Aktif</div>
                <div class="h3 fw-bold">{{ $activeStoreCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
                <div class="text-secondary">Total Toko</div>
                <div class="h3 fw-bold">{{ $allStoreCount }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-3">Transaksi Terakhir</h2>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr class="table-light">
                        <th>Kode</th>
                        <th>Buyer</th>
                        <th>Toko</th>
                        <th>Total Nominal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentTransactions as $transaction)
                        <tr>
                            <td class="fw-semibold">{{ $transaction->order_number }}</td>
                            <td>{{ $transaction->user?->name ?? '-' }}</td>
                            <td>{{ $transaction->items->pluck('tenant.name')->filter()->unique()->join(', ') ?: '-' }}</td>
                            <td>{{ $money((int) $transaction->total_amount) }}</td>
                            <td>{{ $transaction->status }}</td>
                            <td>
                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('finance.transactions.show', $transaction->id) }}">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-secondary">Belum ada transaksi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
