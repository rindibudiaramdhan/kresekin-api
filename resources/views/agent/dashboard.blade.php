@extends('agent.layout', ['title' => 'Agent Dashboard'])

@section('content')
<div class="card shadow-sm mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1 class="h3 mb-1">Agent Dashboard</h1>
            <div class="text-secondary">Halo, {{ $agentName }}. Ini ringkasan akses agent.</div>
            <div class="text-secondary small mt-1">{{ $agentEmail }}</div>
        </div>
        <form method="POST" action="{{ route('agent.logout') }}">
            @csrf
            <button class="btn btn-danger" type="submit">Logout</button>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-secondary">Total Tenant</div>
                <div class="display-6 fw-bold">{{ $tenantCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-secondary">Total Produk</div>
                <div class="display-6 fw-bold">{{ $productCount }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm my-4">
    <div class="card-body d-flex flex-wrap gap-2">
        <a class="btn btn-outline-success" href="{{ route('agent.tenants.index') }}">Kelola Tenant</a>
        <a class="btn btn-success" href="{{ route('agent.tenants.create') }}">Tambah Tenant</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Tenant Terbaru</h2>
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
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Produk Terbaru</h2>
                <div class="vstack gap-2">
            @forelse ($recentProducts as $product)
                <div class="border rounded-3 p-3">
                    <div class="fw-semibold">{{ $product->name ?? 'Product #' . $product->id }}</div>
                    <div class="text-secondary small">Tenant: {{ $product->tenant?->name ?? '-' }}</div>
                </div>
            @empty
                <div class="text-secondary">Belum ada produk.</div>
            @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
