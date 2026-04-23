@extends('agent.layout', ['title' => 'Tenant Detail'])

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Tenant Detail</h1>
        <div class="text-secondary">Detail tenant yang bisa dilihat agent.</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('agent.tenants.index') }}">Kembali</a>
        <a class="btn btn-success" href="{{ route('agent.tenants.edit', $tenant->id) }}">Edit Tenant</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-body">
        <h2 class="h4">{{ $tenant->name }}</h2>
        <div class="text-secondary mb-3">Owner: {{ $tenant->owner?->name ?? '-' }}</div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="text-secondary small">Kategori</div>
                <div>{{ $tenant->category }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-secondary small">Rating</div>
                <div>{{ $tenant->rating ?? 0 }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-secondary small">Latitude</div>
                <div>{{ $tenant->latitude ?? '-' }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-secondary small">Longitude</div>
                <div>{{ $tenant->longitude ?? '-' }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-secondary small">Jam Buka</div>
                <div>{{ $tenant->open_time ?? '-' }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-secondary small">Jam Tutup</div>
                <div>{{ $tenant->close_time ?? '-' }}</div>
            </div>
        </div>

        @if ($tenant->profile_picture_url)
            <div style="margin-top:16px;">
                <div class="text-secondary small">Profile Picture</div>
                <a href="{{ $tenant->profile_picture_url }}" target="_blank" rel="noopener">{{ $tenant->profile_picture_url }}</a>
            </div>
        @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
        <h3 class="h5">Produk Tenant</h3>
        <div class="vstack gap-2">
            @forelse ($products as $product)
                <div class="border rounded-3 p-3">
                    <div class="fw-semibold">{{ $product->name }}</div>
                    <div class="text-secondary small">Kategori: {{ $product->category }}</div>
                    <div class="text-secondary small">Harga: Rp {{ number_format($product->price, 0, ',', '.') }}</div>
                </div>
            @empty
                <div class="text-secondary">Belum ada produk pada tenant ini.</div>
            @endforelse
        </div>
            </div>
        </div>
    </div>
</div>
@endsection
