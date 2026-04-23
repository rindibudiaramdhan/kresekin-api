@extends('seller.layout', ['title' => 'Products'])

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Products</h1>
        <div class="text-secondary">Daftar produk milik seller.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-success" href="{{ route('seller.dashboard') }}">Dashboard</a>
        <a class="btn btn-success" href="{{ route('seller.products.create') }}">Tambah Produk</a>
    </div>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="card shadow-sm">
    <div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead>
            <tr class="table-light">
                <th>Nama</th>
                <th>Tenant</th>
                <th>Kategori</th>
                <th>Harga</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $product->name }}</div>
                        <div class="text-secondary small">{{ $product->weight_label }}</div>
                    </td>
                    <td>{{ $product->tenant?->name }}</td>
                    <td>{{ $product->category }}</td>
                    <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                    <td>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('seller.products.edit', $product->id) }}">Edit</a>
                            <form method="POST" action="{{ route('seller.products.destroy', $product->id) }}" onsubmit="return confirm('Hapus produk ini?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-secondary">Belum ada produk.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

<div class="mt-3">
    {{ $products->links() }}
</div>
@endsection
