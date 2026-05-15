@extends('finance.layout', ['title' => 'Detail Transaksi Finance'])

@section('content')
@php
    $money = fn (int $amount): string => 'Rp. ' . number_format($amount, 0, ',', '.');
    $statusLabels = [
        \App\Models\FinanceTransactionDisbursement::STATUS_PENDING_BUYER_PAYMENT => 'Menunggu Pembayaran Buyer',
        \App\Models\FinanceTransactionDisbursement::STATUS_BUYER_PAYMENT_CONFIRMED => 'Pembayaran Buyer Valid',
        \App\Models\FinanceTransactionDisbursement::STATUS_DISBURSED_TO_SELLER => 'Dana Masuk ke Seller',
    ];
@endphp

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $transaction->order_number }}</h1>
        <div class="text-secondary">{{ $transaction->user?->name ?? 'Buyer' }} &middot; {{ $transaction->status }}</div>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('finance.transactions.index') }}">Kembali</a>
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
                <div class="text-secondary">Subtotal</div>
                <div class="h3 fw-bold">{{ $money((int) $transaction->subtotal_amount) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
                <div class="text-secondary">Ongkir</div>
                <div class="h3 fw-bold">{{ $money((int) $transaction->delivery_fee) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
                <div class="text-secondary">Diskon</div>
                <div class="h3 fw-bold">{{ $money((int) $transaction->discount_amount) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
                <div class="text-secondary">Total</div>
                <div class="h3 fw-bold">{{ $money((int) $transaction->total_amount) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Penyaluran Dana ke Seller</h2>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr class="table-light">
                                <th>Kode Unik</th>
                                <th>Toko</th>
                                <th>Seller</th>
                                <th>Nominal</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transaction->financeDisbursements as $disbursement)
                                <tr>
                                    <td class="fw-semibold">{{ $disbursement->unique_code }}</td>
                                    <td>{{ $disbursement->tenant?->name ?? '-' }}</td>
                                    <td>{{ $disbursement->seller?->name ?? '-' }}</td>
                                    <td>{{ $money($disbursement->amount) }}</td>
                                    <td><span class="badge text-bg-secondary">{{ $statusLabels[$disbursement->status] ?? $disbursement->status }}</span></td>
                                    <td>
                                        @if ($disbursement->status === \App\Models\FinanceTransactionDisbursement::STATUS_PENDING_BUYER_PAYMENT)
                                            <form method="POST" action="{{ route('finance.transactions.confirm-buyer-payment', $transaction->id) }}">
                                                @csrf
                                                <button class="btn btn-success btn-sm" type="submit">Konfirmasi Buyer</button>
                                            </form>
                                        @elseif ($disbursement->status === \App\Models\FinanceTransactionDisbursement::STATUS_BUYER_PAYMENT_CONFIRMED)
                                            <form method="POST" action="{{ route('finance.disbursements.disburse-to-seller', $disbursement->id) }}">
                                                @csrf
                                                <button class="btn btn-success btn-sm" type="submit">Salurkan ke Seller</button>
                                            </form>
                                        @else
                                            <span class="text-secondary small">Selesai</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Item Transaksi</h2>
                <div class="vstack gap-2">
                    @foreach ($transaction->items as $item)
                        <div class="border rounded-3 p-3">
                            <div class="fw-semibold">{{ $item->product_name }}</div>
                            <div class="text-secondary small">{{ $item->tenant?->name ?? '-' }} &middot; {{ $item->quantity }} x {{ $money((int) $item->unit_price) }}</div>
                            <div class="mt-2">{{ $money((int) $item->line_total) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
