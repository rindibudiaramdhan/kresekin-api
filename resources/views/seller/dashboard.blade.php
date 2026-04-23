@extends('seller.layout', ['title' => 'Seller Dashboard'])

@section('content')
<div class="card shadow-sm mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1 class="h3 mb-1">Seller Dashboard</h1>
            <div class="text-secondary">Kelola tenant dan produk seller dari sini.</div>
        </div>
        <form method="POST" action="{{ route('seller.logout') }}">
            @csrf
            <button class="btn btn-danger" type="submit">Logout</button>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-secondary">Jumlah Tenant</div>
                <div class="display-6 fw-bold">{{ $tenantCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-secondary">Jumlah Produk</div>
                <div class="display-6 fw-bold">{{ $productCount }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm my-4">
    <div class="card-body">
        <a class="btn btn-success" href="{{ route('seller.products.index') }}">Kelola Produk</a>
    </div>
</div>
@endsection
