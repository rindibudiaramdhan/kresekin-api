@extends('seller.layout', ['title' => $product->exists ? 'Edit Product' : 'Create Product'])

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $product->exists ? 'Edit Product' : 'Create Product' }}</h1>
        <div class="text-secondary">Isi data produk untuk tenant yang kamu miliki.</div>
    </div>
    <div>
        <a class="btn btn-outline-secondary" href="{{ route('seller.products.index') }}">Kembali</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
    <form method="POST" action="{{ $product->exists ? route('seller.products.update', $product->id) : route('seller.products.store') }}" class="vstack gap-3">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-md-6">
                <label for="tenant_id" class="form-label">Tenant</label>
                <select id="tenant_id" name="tenant_id" class="form-select" required>
                    <option value="">Pilih tenant</option>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}" @selected(old('tenant_id', $product->tenant_id) == $tenant->id)>{{ $tenant->name }}</option>
                    @endforeach
                </select>
                @error('tenant_id')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="category" class="form-label">Kategori</label>
                <select id="category" name="category" class="form-select" required>
                    <option value="">Pilih kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected(old('category', $product->category) === $category)>{{ $category }}</option>
                    @endforeach
                </select>
                @error('category')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Nama Produk</label>
                <input class="form-control" id="name" name="name" value="{{ old('name', $product->name) }}" required>
                @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="image_url" class="form-label">Image URL</label>
                <input class="form-control" id="image_url" name="image_url" value="{{ old('image_url', $product->image_url) }}">
                @error('image_url')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label for="price" class="form-label">Harga</label>
                <input class="form-control" id="price" name="price" type="number" min="0" value="{{ old('price', $product->price) }}" required>
                @error('price')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="original_price" class="form-label">Harga Awal</label>
                <input class="form-control" id="original_price" name="original_price" type="number" min="0" value="{{ old('original_price', $product->original_price) }}">
                @error('original_price')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="weight_label" class="form-label">Label Berat</label>
                <input class="form-control" id="weight_label" name="weight_label" value="{{ old('weight_label', $product->weight_label) }}">
                @error('weight_label')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="delivery_estimate" class="form-label">Estimasi Pengiriman</label>
                <input class="form-control" id="delivery_estimate" name="delivery_estimate" value="{{ old('delivery_estimate', $product->delivery_estimate) }}">
                @error('delivery_estimate')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6"></div>
        </div>

        <div>
            <label for="description" class="form-label">Deskripsi</label>
            <textarea class="form-control" id="description" name="description" rows="5">{{ old('description', $product->description) }}</textarea>
            @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        <div>
            <button class="btn btn-success" type="submit">{{ $product->exists ? 'Update' : 'Simpan' }}</button>
        </div>
    </form>
    </div>
</div>
@endsection
