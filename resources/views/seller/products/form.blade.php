@extends('seller.layout', ['title' => $product->exists ? 'Edit Product' : 'Create Product'])

@section('content')
<div class="topbar">
    <div>
        <h1 style="margin:0;">{{ $product->exists ? 'Edit Product' : 'Create Product' }}</h1>
        <div class="muted">Isi data produk untuk tenant yang kamu miliki.</div>
    </div>
    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('seller.products.index') }}">Kembali</a>
    </div>
</div>

<div class="card">
    <form method="POST" action="{{ $product->exists ? route('seller.products.update', $product->id) : route('seller.products.store') }}" class="grid" style="gap:16px;">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif

        <div class="grid grid-2">
            <div>
                <label for="tenant_id">Tenant</label>
                <select id="tenant_id" name="tenant_id" required>
                    <option value="">Pilih tenant</option>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}" @selected(old('tenant_id', $product->tenant_id) == $tenant->id)>{{ $tenant->name }}</option>
                    @endforeach
                </select>
                @error('tenant_id')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div>
                <label for="category">Kategori</label>
                <select id="category" name="category" required>
                    <option value="">Pilih kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected(old('category', $product->category) === $category)>{{ $category }}</option>
                    @endforeach
                </select>
                @error('category')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="grid grid-2">
            <div>
                <label for="name">Nama Produk</label>
                <input class="input" id="name" name="name" value="{{ old('name', $product->name) }}" required>
                @error('name')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div>
                <label for="image_url">Image URL</label>
                <input class="input" id="image_url" name="image_url" value="{{ old('image_url', $product->image_url) }}">
                @error('image_url')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="grid grid-3">
            <div>
                <label for="price">Harga</label>
                <input class="input" id="price" name="price" type="number" min="0" value="{{ old('price', $product->price) }}" required>
                @error('price')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div>
                <label for="original_price">Harga Awal</label>
                <input class="input" id="original_price" name="original_price" type="number" min="0" value="{{ old('original_price', $product->original_price) }}">
                @error('original_price')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div>
                <label for="weight_label">Label Berat</label>
                <input class="input" id="weight_label" name="weight_label" value="{{ old('weight_label', $product->weight_label) }}">
                @error('weight_label')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="grid grid-2">
            <div>
                <label for="delivery_estimate">Estimasi Pengiriman</label>
                <input class="input" id="delivery_estimate" name="delivery_estimate" value="{{ old('delivery_estimate', $product->delivery_estimate) }}">
                @error('delivery_estimate')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div></div>
        </div>

        <div>
            <label for="description">Deskripsi</label>
            <textarea class="input" id="description" name="description" rows="5">{{ old('description', $product->description) }}</textarea>
            @error('description')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="actions">
            <button class="btn" type="submit">{{ $product->exists ? 'Update' : 'Simpan' }}</button>
        </div>
    </form>
</div>
@endsection
