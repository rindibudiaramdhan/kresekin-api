@extends('seller.layout', ['title' => 'Seller Dashboard'])

@section('content')
<div class="topbar">
    <div>
        <h1 style="margin:0;">Seller Dashboard</h1>
        <div class="muted">Kelola tenant dan produk seller dari sini.</div>
    </div>
    <form method="POST" action="{{ route('seller.logout') }}">
        @csrf
        <button class="btn btn-secondary" type="submit">Logout</button>
    </form>
</div>

<div class="grid grid-2">
    <div class="card">
        <div class="muted">Jumlah Tenant</div>
        <div style="font-size:40px; font-weight:700;">{{ $tenantCount }}</div>
    </div>
    <div class="card">
        <div class="muted">Jumlah Produk</div>
        <div style="font-size:40px; font-weight:700;">{{ $productCount }}</div>
    </div>
</div>

<div class="card" style="margin-top:16px;">
    <div class="actions">
        <a class="btn" href="{{ route('seller.products.index') }}">Kelola Produk</a>
    </div>
</div>
@endsection
