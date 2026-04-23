@extends('agent.layout', ['title' => 'Tenant Detail'])

@section('content')
<div class="topbar">
    <div>
        <h1 style="margin:0;">Tenant Detail</h1>
        <div class="muted">Detail tenant yang bisa dilihat agent.</div>
    </div>
    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('agent.tenants.index') }}">Kembali</a>
        <a class="btn" href="{{ route('agent.tenants.edit', $tenant->id) }}">Edit Tenant</a>
    </div>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; align-items:start;">
    <div class="card">
        <h2 style="margin-top:0;">{{ $tenant->name }}</h2>
        <div class="muted" style="margin-bottom:12px;">Owner: {{ $tenant->owner?->name ?? '-' }}</div>

        <div class="grid grid-2">
            <div>
                <div class="muted">Kategori</div>
                <div>{{ $tenant->category }}</div>
            </div>
            <div>
                <div class="muted">Rating</div>
                <div>{{ $tenant->rating ?? 0 }}</div>
            </div>
            <div>
                <div class="muted">Latitude</div>
                <div>{{ $tenant->latitude ?? '-' }}</div>
            </div>
            <div>
                <div class="muted">Longitude</div>
                <div>{{ $tenant->longitude ?? '-' }}</div>
            </div>
            <div>
                <div class="muted">Jam Buka</div>
                <div>{{ $tenant->open_time ?? '-' }}</div>
            </div>
            <div>
                <div class="muted">Jam Tutup</div>
                <div>{{ $tenant->close_time ?? '-' }}</div>
            </div>
        </div>

        @if ($tenant->profile_picture_url)
            <div style="margin-top:16px;">
                <div class="muted">Profile Picture</div>
                <a href="{{ $tenant->profile_picture_url }}" target="_blank" rel="noopener">{{ $tenant->profile_picture_url }}</a>
            </div>
        @endif
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Produk Tenant</h3>
        <div class="grid" style="gap:12px;">
            @forelse ($products as $product)
                <div style="padding:12px; border:1px solid #e5e7eb; border-radius:12px;">
                    <div style="font-weight:600;">{{ $product->name }}</div>
                    <div class="muted">Kategori: {{ $product->category }}</div>
                    <div class="muted">Harga: Rp {{ number_format($product->price, 0, ',', '.') }}</div>
                </div>
            @empty
                <div class="muted">Belum ada produk pada tenant ini.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
