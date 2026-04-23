@extends('agent.layout', ['title' => $tenant->exists ? 'Edit Tenant' : 'Create Tenant'])

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $tenant->exists ? 'Edit Tenant' : 'Create Tenant' }}</h1>
        <div class="text-secondary">Kelola tenant yang tersedia di sistem.</div>
    </div>
    <div>
        <a class="btn btn-outline-secondary" href="{{ route('agent.tenants.index') }}">Kembali</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
    <form method="POST" action="{{ $tenant->exists ? route('agent.tenants.update', $tenant->id) : route('agent.tenants.store') }}" class="vstack gap-3">
        @csrf
        @if ($tenant->exists)
            @method('PUT')
        @endif

        <div class="border rounded-3 p-3 bg-light">
            <div class="fw-semibold mb-2">Owner Seller</div>
            <div class="vstack gap-3">
                <div class="d-flex flex-wrap gap-3">
                    <label class="form-check-label fw-semibold">
                        <input type="radio" name="owner_mode" value="existing" {{ old('owner_mode', $ownerMode ?? 'existing') === 'existing' ? 'checked' : '' }}>
                        Pilih seller existing
                    </label>
                    <label class="form-check-label fw-semibold">
                        <input type="radio" name="owner_mode" value="new" {{ old('owner_mode', $ownerMode ?? 'existing') === 'new' ? 'checked' : '' }}>
                        Buat seller baru
                    </label>
                </div>

                <div>
                    <label for="owner_user_id" class="form-label">Owner Seller Existing</label>
                    <select id="owner_user_id" name="owner_user_id" class="form-select">
                        <option value="">Pilih seller</option>
                        @foreach ($sellers as $seller)
                            <option value="{{ $seller->id }}" @selected(old('owner_user_id', $tenant->owner_user_id) == $seller->id)>{{ $seller->name }} ({{ $seller->email }})</option>
                        @endforeach
                    </select>
                    @error('owner_user_id')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="seller_name" class="form-label">Nama Seller Baru</label>
                        <input class="form-control" id="seller_name" name="seller_name" value="{{ old('seller_name') }}">
                        @error('seller_name')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="seller_email" class="form-label">Email Seller Baru</label>
                        <input class="form-control" id="seller_email" name="seller_email" type="email" value="{{ old('seller_email') }}">
                        @error('seller_email')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="seller_password" class="form-label">Password Seller Baru</label>
                        <input class="form-control" id="seller_password" name="seller_password" type="password">
                        @error('seller_password')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 text-secondary small d-flex align-items-end">
                        Jika memilih seller baru, sistem akan membuat akun seller sekaligus.
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="category" class="form-label">Kategori</label>
                <select id="category" name="category" class="form-select" required>
                    <option value="">Pilih kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected(old('category', $tenant->category) === $category)>{{ $category }}</option>
                    @endforeach
                </select>
                @error('category')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Nama Tenant</label>
                <input class="form-control" id="name" name="name" value="{{ old('name', $tenant->name) }}" required>
                @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="profile_picture_url" class="form-label">Profile Picture URL</label>
                <input class="form-control" id="profile_picture_url" name="profile_picture_url" value="{{ old('profile_picture_url', $tenant->profile_picture_url) }}">
                @error('profile_picture_url')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label for="rating" class="form-label">Rating</label>
                <input class="form-control" id="rating" name="rating" type="number" min="0" max="5" step="0.1" value="{{ old('rating', $tenant->rating) }}">
                @error('rating')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="latitude" class="form-label">Latitude</label>
                <input class="form-control" id="latitude" name="latitude" type="number" step="any" value="{{ old('latitude', $tenant->latitude) }}">
                @error('latitude')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="longitude" class="form-label">Longitude</label>
                <input class="form-control" id="longitude" name="longitude" type="number" step="any" value="{{ old('longitude', $tenant->longitude) }}">
                @error('longitude')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="open_time" class="form-label">Jam Buka</label>
                <input class="form-control" id="open_time" name="open_time" type="time" value="{{ old('open_time', $tenant->open_time) }}">
                @error('open_time')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="close_time" class="form-label">Jam Tutup</label>
                <input class="form-control" id="close_time" name="close_time" type="time" value="{{ old('close_time', $tenant->close_time) }}">
                @error('close_time')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-success" type="submit">{{ $tenant->exists ? 'Update' : 'Simpan' }}</button>
        </div>
    </form>
    </div>
</div>
@endsection
