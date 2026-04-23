@extends('agent.layout', ['title' => $tenant->exists ? 'Edit Tenant' : 'Create Tenant'])

@section('content')
<div class="topbar">
    <div>
        <h1 style="margin:0;">{{ $tenant->exists ? 'Edit Tenant' : 'Create Tenant' }}</h1>
        <div class="muted">Kelola tenant yang tersedia di sistem.</div>
    </div>
    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('agent.tenants.index') }}">Kembali</a>
    </div>
</div>

<div class="card">
    <form method="POST" action="{{ $tenant->exists ? route('agent.tenants.update', $tenant->id) : route('agent.tenants.store') }}" class="grid" style="gap:16px;">
        @csrf
        @if ($tenant->exists)
            @method('PUT')
        @endif

        <div class="card" style="background:#f9fafb; border-style:dashed;">
            <div style="font-weight:700; margin-bottom:8px;">Owner Seller</div>
            <div class="grid" style="gap:12px;">
                <div class="actions">
                    <label style="margin:0; font-weight:600;">
                        <input type="radio" name="owner_mode" value="existing" {{ old('owner_mode', $ownerMode ?? 'existing') === 'existing' ? 'checked' : '' }}>
                        Pilih seller existing
                    </label>
                    <label style="margin:0; font-weight:600;">
                        <input type="radio" name="owner_mode" value="new" {{ old('owner_mode', $ownerMode ?? 'existing') === 'new' ? 'checked' : '' }}>
                        Buat seller baru
                    </label>
                </div>

                <div>
                    <label for="owner_user_id">Owner Seller Existing</label>
                    <select id="owner_user_id" name="owner_user_id">
                        <option value="">Pilih seller</option>
                        @foreach ($sellers as $seller)
                            <option value="{{ $seller->id }}" @selected(old('owner_user_id', $tenant->owner_user_id) == $seller->id)>{{ $seller->name }} ({{ $seller->email }})</option>
                        @endforeach
                    </select>
                    @error('owner_user_id')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="grid grid-2">
                    <div>
                        <label for="seller_name">Nama Seller Baru</label>
                        <input class="input" id="seller_name" name="seller_name" value="{{ old('seller_name') }}">
                        @error('seller_name')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="seller_email">Email Seller Baru</label>
                        <input class="input" id="seller_email" name="seller_email" type="email" value="{{ old('seller_email') }}">
                        @error('seller_email')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="grid grid-2">
                    <div>
                        <label for="seller_password">Password Seller Baru</label>
                        <input class="input" id="seller_password" name="seller_password" type="password">
                        @error('seller_password')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="muted" style="display:flex; align-items:end;">
                        Jika memilih seller baru, sistem akan membuat akun seller sekaligus.
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-2">
            <div>
                <label for="category">Kategori</label>
                <select id="category" name="category" required>
                    <option value="">Pilih kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected(old('category', $tenant->category) === $category)>{{ $category }}</option>
                    @endforeach
                </select>
                @error('category')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="grid grid-2">
            <div>
                <label for="name">Nama Tenant</label>
                <input class="input" id="name" name="name" value="{{ old('name', $tenant->name) }}" required>
                @error('name')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div>
                <label for="profile_picture_url">Profile Picture URL</label>
                <input class="input" id="profile_picture_url" name="profile_picture_url" value="{{ old('profile_picture_url', $tenant->profile_picture_url) }}">
                @error('profile_picture_url')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="grid grid-3">
            <div>
                <label for="rating">Rating</label>
                <input class="input" id="rating" name="rating" type="number" min="0" max="5" step="0.1" value="{{ old('rating', $tenant->rating) }}">
                @error('rating')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div>
                <label for="latitude">Latitude</label>
                <input class="input" id="latitude" name="latitude" type="number" step="any" value="{{ old('latitude', $tenant->latitude) }}">
                @error('latitude')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div>
                <label for="longitude">Longitude</label>
                <input class="input" id="longitude" name="longitude" type="number" step="any" value="{{ old('longitude', $tenant->longitude) }}">
                @error('longitude')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="grid grid-2">
            <div>
                <label for="open_time">Jam Buka</label>
                <input class="input" id="open_time" name="open_time" type="time" value="{{ old('open_time', $tenant->open_time) }}">
                @error('open_time')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div>
                <label for="close_time">Jam Tutup</label>
                <input class="input" id="close_time" name="close_time" type="time" value="{{ old('close_time', $tenant->close_time) }}">
                @error('close_time')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="actions">
            <button class="btn" type="submit">{{ $tenant->exists ? 'Update' : 'Simpan' }}</button>
        </div>
    </form>
</div>
@endsection
