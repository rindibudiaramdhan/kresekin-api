@extends('agent.layout', ['title' => 'Agent Sellers'])

@section('content')
@php
    $money = fn (int $amount): string => 'Rp. ' . number_format($amount, 0, ',', '.');
@endphp

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Seller Dalam Lingkup Agent</h1>
        <div class="text-secondary">Pantau seller, toko, transaksi, revenue, dan komisi agent.</div>
    </div>
    <a class="btn btn-success" href="{{ route('agent.tenants.create') }}">Tambah Tenant</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="table-light">
                    <th>Seller</th>
                    <th>Toko</th>
                    <th>Transaksi</th>
                    <th>Total Revenue</th>
                    <th>Komisi Agent</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sellers as $seller)
                    @php
                        $tenantIds = $seller->ownedTenants->pluck('id')->all();
                        $revenue = \App\Http\Controllers\Web\AgentSellerController::completedRevenue($tenantIds);
                        $commission = $calculator->commissionFromRevenue($revenue);
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $seller->name ?? 'Seller #' . $seller->id }}</div>
                            <div class="text-secondary small">{{ $seller->email ?? '-' }} &middot; {{ $seller->phone ?? '-' }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $seller->ownedTenants->count() }}</div>
                            <div class="text-secondary small">{{ $seller->ownedTenants->pluck('name')->take(2)->join(', ') }}</div>
                        </td>
                        <td>{{ \App\Http\Controllers\Web\AgentSellerController::transactionCount($tenantIds) }}</td>
                        <td>{{ $money($revenue) }}</td>
                        <td>{{ $money($commission) }}</td>
                        <td>
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('agent.sellers.show', $seller->id) }}">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-secondary">Belum ada seller dalam lingkup agent.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $sellers->links() }}
</div>
@endsection
