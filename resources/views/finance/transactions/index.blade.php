@extends('finance.layout', ['title' => 'Finance Transactions'])

@section('content')
@php
    $money = fn (int $amount): string => 'Rp. ' . number_format($amount, 0, ',', '.');
@endphp

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Transaksi Finance</h1>
        <div class="text-secondary">Konfirmasi pembayaran buyer dan salurkan dana transaksi ke seller.</div>
    </div>
    <a class="btn btn-outline-success" href="{{ route('finance.dashboard') }}">Dashboard</a>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('finance.transactions.index') }}" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="status" class="form-label">Status Penyaluran</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $status => $label)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-7 d-flex gap-2">
                <button class="btn btn-success" type="submit">Terapkan</button>
                <a class="btn btn-outline-secondary" href="{{ route('finance.transactions.index') }}">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="table-light">
                    <th>Kode Unik</th>
                    <th>Order</th>
                    <th>Toko</th>
                    <th>Seller</th>
                    <th>Total Nominal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($disbursements as $disbursement)
                    <tr>
                        <td class="fw-semibold">{{ $disbursement->unique_code }}</td>
                        <td>
                            <div>{{ $disbursement->transaction?->order_number }}</div>
                            <div class="text-secondary small">{{ $disbursement->transaction?->status }}</div>
                        </td>
                        <td>{{ $disbursement->tenant?->name ?? '-' }}</td>
                        <td>{{ $disbursement->seller?->name ?? '-' }}</td>
                        <td>{{ $money($disbursement->amount) }}</td>
                        <td><span class="badge text-bg-secondary">{{ $statuses[$disbursement->status] ?? $disbursement->status }}</span></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('finance.transactions.show', $disbursement->transaction_id) }}">Detail</a>
                                @if ($disbursement->status === \App\Models\FinanceTransactionDisbursement::STATUS_PENDING_BUYER_PAYMENT)
                                    <form method="POST" action="{{ route('finance.transactions.confirm-buyer-payment', $disbursement->transaction_id) }}">
                                        @csrf
                                        <button class="btn btn-success btn-sm" type="submit">Konfirmasi Buyer</button>
                                    </form>
                                @elseif ($disbursement->status === \App\Models\FinanceTransactionDisbursement::STATUS_BUYER_PAYMENT_CONFIRMED)
                                    <form method="POST" action="{{ route('finance.disbursements.disburse-to-seller', $disbursement->id) }}">
                                        @csrf
                                        <button class="btn btn-success btn-sm" type="submit">Salurkan ke Seller</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-secondary">Belum ada transaksi untuk finance.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $disbursements->links() }}
</div>
@endsection
