@extends('agent.layout', ['title' => 'Profil Pencairan Agent'])

@section('content')
@php
    $money = fn (int $amount): string => 'Rp. ' . number_format($amount, 0, ',', '.');
@endphp

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Profil Agent</h1>
        <div class="text-secondary">Data rekening dipakai untuk pengajuan pencairan komisi.</div>
    </div>
    <a class="btn btn-outline-success" href="{{ route('agent.withdrawals.index') }}">Pencairan Komisi</a>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Saldo Komisi</h2>
                <div class="text-secondary mt-3">Total Komisi</div>
                <div class="h3 fw-bold">{{ $money($summary['total_commission']) }}</div>
                <div class="text-secondary mt-3">Tersedia Dicairkan</div>
                <div class="h3 fw-bold">{{ $money($summary['available_commission']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('agent.profile.update') }}" class="row g-3">
                    @csrf
                    @method('PUT')
                    <div class="col-md-6">
                        <label for="name" class="form-label">Nama Agent</label>
                        <input class="form-control" id="name" name="name" value="{{ old('name', $agent->name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $agent->email) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Nomor HP</label>
                        <input class="form-control" id="phone" name="phone" value="{{ old('phone', $agent->phone) }}">
                    </div>
                    <div class="col-md-6">
                        <label for="bank_name" class="form-label">Nama Bank</label>
                        <input class="form-control" id="bank_name" name="bank_name" value="{{ old('bank_name', $agent->bank_name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="bank_account_name" class="form-label">Nama Pemilik Rekening</label>
                        <input class="form-control" id="bank_account_name" name="bank_account_name" value="{{ old('bank_account_name', $agent->bank_account_name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="bank_account_number" class="form-label">Nomor Rekening</label>
                        <input class="form-control" id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number', $agent->bank_account_number) }}" required>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-success" type="submit">Simpan Profil</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
