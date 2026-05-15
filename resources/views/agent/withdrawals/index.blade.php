@extends('agent.layout', ['title' => 'Pencairan Komisi Agent'])

@section('content')
@php
    $money = fn (int $amount): string => 'Rp. ' . number_format($amount, 0, ',', '.');
    $profileComplete = filled($agent->bank_name) && filled($agent->bank_account_name) && filled($agent->bank_account_number);
@endphp

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Pencairan Komisi</h1>
        <div class="text-secondary">Ajukan pencairan fee komisi berdasarkan transaksi selesai.</div>
    </div>
    <a class="btn btn-outline-success" href="{{ route('agent.profile.edit') }}">Profil Rekening</a>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
                <div class="text-secondary">Total Revenue</div>
                <div class="h3 fw-bold">{{ $money($summary['total_revenue']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
                <div class="text-secondary">Total Komisi</div>
                <div class="h3 fw-bold">{{ $money($summary['total_commission']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
                <div class="text-secondary">Sudah Diajukan</div>
                <div class="h3 fw-bold">{{ $money($summary['withdrawn_commission']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
                <div class="text-secondary">Tersedia</div>
                <div class="h3 fw-bold">{{ $money($summary['available_commission']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Ajukan Pencairan</h2>
                @unless ($profileComplete)
                    <div class="alert alert-warning">Lengkapi profil rekening sebelum mengajukan pencairan.</div>
                @endunless
                <form method="POST" action="{{ route('agent.withdrawals.store') }}" class="vstack gap-3">
                    @csrf
                    <div>
                        <label for="amount" class="form-label">Nominal</label>
                        <input class="form-control" id="amount" name="amount" type="number" min="1" max="{{ $summary['available_commission'] }}" value="{{ old('amount', $summary['available_commission']) }}" required @disabled(! $profileComplete)>
                    </div>
                    <div>
                        <label for="note" class="form-label">Catatan</label>
                        <textarea class="form-control" id="note" name="note" rows="3" @disabled(! $profileComplete)>{{ old('note') }}</textarea>
                    </div>
                    <button class="btn btn-success" type="submit" @disabled(! $profileComplete || $summary['available_commission'] <= 0)>Ajukan Pencairan</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Riwayat Pencairan</h2>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr class="table-light">
                                <th>Nominal</th>
                                <th>Status</th>
                                <th>Catatan</th>
                                <th>Diajukan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($withdrawals as $withdrawal)
                                <tr>
                                    <td class="fw-semibold">{{ $money($withdrawal->amount) }}</td>
                                    <td><span class="badge text-bg-secondary">{{ $withdrawal->status }}</span></td>
                                    <td>{{ $withdrawal->note ?? '-' }}</td>
                                    <td>{{ $withdrawal->requested_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-secondary">Belum ada pencairan komisi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $withdrawals->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
