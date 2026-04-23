@extends('agent.layout', ['title' => 'Agent Tenants'])

@section('content')
<div class="topbar">
    <div>
        <h1 style="margin:0;">Tenant Management</h1>
        <div class="muted">CRUD tenant khusus untuk agent.</div>
    </div>
    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('agent.dashboard') }}">Dashboard</a>
        <a class="btn" href="{{ route('agent.tenants.create') }}">Tambah Tenant</a>
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
                        <strong>{{ $tenant->name }}</strong><br>
                        <span class="muted">ID: {{ $tenant->id }}</span>
                    </td>
                    <td>{{ $tenant->owner?->name ?? '-' }}</td>
                    <td>{{ $tenant->category }}</td>
                    <td class="muted">
                        {{ $tenant->latitude ?? '-' }}, {{ $tenant->longitude ?? '-' }}
                    </td>
                    <td>{{ $tenant->operatingHoursLabel() ?? '-' }}</td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-secondary" href="{{ route('agent.tenants.edit', $tenant->id) }}">Edit</a>
                            <form method="POST" action="{{ route('agent.tenants.destroy', $tenant->id) }}" onsubmit="return confirm('Hapus tenant ini?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted">Belum ada tenant.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:16px;">
    {{ $tenants->links() }}
</div>
@endsection
