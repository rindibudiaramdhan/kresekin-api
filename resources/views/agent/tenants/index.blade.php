@extends('agent.layout', ['title' => 'Agent Tenants'])

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Tenant Management</h1>
        <div class="text-secondary">CRUD tenant khusus untuk agent.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-success" href="{{ route('agent.dashboard') }}">Dashboard</a>
        <a class="btn btn-success" href="{{ route('agent.tenants.create') }}">Tambah Tenant</a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
    <form method="GET" action="{{ route('agent.tenants.index') }}" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label for="seller" class="form-label">Filter Seller</label>
            <select id="seller" name="seller" class="form-select">
                <option value="">Semua seller</option>
                @foreach ($sellers as $seller)
                    <option value="{{ $seller->id }}" @selected((string) $selectedSeller === (string) $seller->id)>{{ $seller->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label for="category" class="form-label">Filter Kategori</label>
            <select id="category" name="category" class="form-select">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected($selectedCategory === $category)>{{ $category }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button class="btn btn-success" type="submit">Terapkan</button>
            <a class="btn btn-outline-secondary" href="{{ route('agent.tenants.index') }}">Reset</a>
        </div>
    </form>
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
                <th>Owner</th>
                <th>Kategori</th>
                <th>Lokasi</th>
                <th>Jam Operasional</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tenants as $tenant)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $tenant->name }}</div>
                        <div class="text-secondary small">ID: {{ $tenant->id }}</div>
                    </td>
                    <td>{{ $tenant->owner?->name ?? '-' }}</td>
                    <td>{{ $tenant->category }}</td>
                    <td class="text-secondary">
                        {{ $tenant->latitude ?? '-' }}, {{ $tenant->longitude ?? '-' }}
                    </td>
                    <td>{{ $tenant->operatingHoursLabel() ?? '-' }}</td>
                    <td>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('agent.tenants.show', $tenant->id) }}">Detail</a>
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('agent.tenants.edit', $tenant->id) }}">Edit</a>
                            <form method="POST" action="{{ route('agent.tenants.destroy', $tenant->id) }}" onsubmit="return confirm('Hapus tenant ini?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-secondary">Belum ada tenant.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

<div class="mt-3">
    {{ $tenants->links() }}
</div>
@endsection
