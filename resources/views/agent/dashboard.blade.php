@extends('agent.layout', ['title' => 'Agent Dashboard'])

@section('content')
<div class="card" style="margin-bottom:16px;">
    <div style="display:flex; justify-content:space-between; gap:16px; align-items:flex-start; flex-wrap:wrap;">
        <div>
            <h1 style="margin:0;">Agent Dashboard</h1>
            <div class="muted">Halo, {{ $agentName }}. Ini ringkasan akses agent.</div>
            <div class="muted" style="margin-top:4px;">{{ $agentEmail }}</div>
        </div>
        <form method="POST" action="{{ route('agent.logout') }}">
            @csrf
            <button class="btn" type="submit" style="background:#b91c1c;">Logout</button>
        </form>
    </div>
</div>

<div class="grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
    <div class="card">
        <div class="muted">Total User</div>
        <div style="font-size:40px; font-weight:700;">{{ $totalUsers }}</div>
    </div>
    <div class="card">
        <div class="muted">Total Tenant</div>
        <div style="font-size:40px; font-weight:700;">{{ $tenantCount }}</div>
    </div>
    <div class="card">
        <div class="muted">Total Produk</div>
        <div style="font-size:40px; font-weight:700;">{{ $productCount }}</div>
    </div>
</div>

<div class="grid" style="grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top:16px;">
    <div class="card">
        <div class="muted">Buyer</div>
        <div style="font-size:28px; font-weight:700;">{{ $buyerCount }}</div>
    </div>
    <div class="card">
        <div class="muted">Seller</div>
        <div style="font-size:28px; font-weight:700;">{{ $sellerCount }}</div>
    </div>
    <div class="card">
        <div class="muted">Agent</div>
        <div style="font-size:28px; font-weight:700;">{{ $agentCount }}</div>
    </div>
</div>

<div class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top:16px;">
    <div class="card">
        <h2 style="margin-top:0;">Tenant Terbaru</h2>
        <div class="grid" style="gap:12px;">
            @forelse ($recentTenants as $tenant)
                <div style="padding:12px; border:1px solid #e5e7eb; border-radius:12px;">
                    <div style="font-weight:600;">{{ $tenant->name ?? 'Tenant #' . $tenant->id }}</div>
                    <div class="muted">ID: {{ $tenant->id }}</div>
                </div>
            @empty
                <div class="muted">Belum ada tenant.</div>
            @endforelse
        </div>
    </div>
    <div class="card">
        <h2 style="margin-top:0;">Produk Terbaru</h2>
        <div class="grid" style="gap:12px;">
            @forelse ($recentProducts as $product)
                <div style="padding:12px; border:1px solid #e5e7eb; border-radius:12px;">
                    <div style="font-weight:600;">{{ $product->name ?? 'Product #' . $product->id }}</div>
                    <div class="muted">Tenant: {{ $product->tenant?->name ?? '-' }}</div>
                </div>
            @empty
                <div class="muted">Belum ada produk.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
