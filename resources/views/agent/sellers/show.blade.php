@extends('agent.layout', ['title' => 'Detail Seller Agent'])

@section('content')
@php
    $money = fn (int $amount): string => 'Rp. ' . number_format($amount, 0, ',', '.');
    $tenantIds = $seller->ownedTenants->pluck('id')->all();
    $revenue = \App\Http\Controllers\Web\AgentSellerController::completedRevenue($tenantIds);
    $commission = $calculator->commissionFromRevenue($revenue);
@endphp

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $seller->name ?? 'Seller #' . $seller->id }}</h1>
        <div class="text-secondary">{{ $seller->email ?? '-' }} &middot; {{ $seller->phone ?? '-' }}</div>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('agent.sellers.index') }}">Kembali</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
                <div class="text-secondary">Jumlah Toko</div>
                <div class="h3 fw-bold">{{ $seller->ownedTenants->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
                <div class="text-secondary">Transaksi</div>
                <div class="h3 fw-bold">{{ \App\Http\Controllers\Web\AgentSellerController::transactionCount($tenantIds) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
                <div class="text-secondary">Total Revenue</div>
                <div class="h3 fw-bold">{{ $money($revenue) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
                <div class="text-secondary">Komisi Agent</div>
                <div class="h3 fw-bold">{{ $money($commission) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Toko Seller</h2>
                <div class="vstack gap-2">
                    @forelse ($seller->ownedTenants as $tenant)
                        @php
                            $tenantRevenue = \App\Http\Controllers\Web\AgentSellerController::tenantCompletedRevenue($tenant);
                        @endphp
                        <div class="border rounded-3 p-3">
                            <div class="d-flex justify-content-between gap-3">
                                <div>
                                    <div class="fw-semibold">{{ $tenant->name }}</div>
                                    <div class="text-secondary small">{{ $tenant->category }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold">{{ $money($tenantRevenue) }}</div>
                                    <div class="text-secondary small">{{ $money($calculator->commissionFromRevenue($tenantRevenue)) }} komisi</div>
                                </div>
                            </div>
                            <a class="btn btn-outline-secondary btn-sm mt-3" href="{{ route('agent.tenants.show', $tenant->id) }}">Detail Toko</a>
                        </div>
                    @empty
                        <div class="text-secondary">Seller belum memiliki toko dalam lingkup agent.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Transaksi Seller</h2>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr class="table-light">
                                <th>Order</th>
                                <th>Toko</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->order_number }}</td>
                                    <td>{{ $transaction->items->first()?->tenant?->name ?? '-' }}</td>
                                    <td>{{ $transaction->status }}</td>
                                    <td>{{ $money((int) $transaction->total_amount) }}</td>
                                    <td>{{ $transaction->transaction_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-secondary">Belum ada transaksi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
