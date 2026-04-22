@extends('seller.layout', ['title' => 'Products'])

@section('content')
<div class="topbar">
    <div>
        <h1 style="margin:0;">Products</h1>
        <div class="muted">Daftar produk milik seller.</div>
    </div>
    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('seller.dashboard') }}">Dashboard</a>
        <a class="btn" href="{{ route('seller.products.create') }}">Tambah Produk</a>
    </div>
</div>

@if (session('status'))
    <div class="alert">{{ session('status') }}</div>
@endif

<div class="card">
    <table>
        <thead>
            <tr>
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
                        <strong>{{ $product->name }}</strong><br>
                        <span class="muted">{{ $product->weight_label }}</span>
                    </td>
                    <td>{{ $product->tenant?->name }}</td>
                    <td>{{ $product->category }}</td>
                    <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-secondary" href="{{ route('seller.products.edit', $product->id) }}">Edit</a>
                            <form method="POST" action="{{ route('seller.products.destroy', $product->id) }}" onsubmit="return confirm('Hapus produk ini?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="muted">Belum ada produk.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:16px;">
    {{ $products->links() }}
</div>
@endsection
