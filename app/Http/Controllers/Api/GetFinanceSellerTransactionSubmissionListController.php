<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinanceTransactionDisbursement;
use App\Models\Transaction;
use App\Support\FinanceDisbursementSyncer;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GetFinanceSellerTransactionSubmissionListController extends Controller
{
    public function __invoke(Request $request, FinanceDisbursementSyncer $syncer): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['paid', 'requested', 'approved', 'rejected'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 5);

        Transaction::query()
            ->with('items.tenant')
            ->whereDoesntHave('financeDisbursements')
            ->chunkById(100, fn ($transactions) => $transactions->each(fn (Transaction $transaction) => $syncer->syncForTransaction($transaction)));

        $submissions = FinanceTransactionDisbursement::query()
            ->with(['seller', 'tenant', 'transaction.user'])
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('unique_code', 'like', '%'.$search.'%')
                        ->orWhereHas('tenant', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('seller', fn ($query) => $query
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%'))
                        ->orWhereHas('transaction', fn ($query) => $query->where('order_number', 'like', '%'.$search.'%'));
                });
            })
            ->when($validated['status'] ?? null, fn ($query, string $status) => $this->applyStatusFilter($query, $status))
            ->when($validated['date_from'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($validated['date_to'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'message' => 'Daftar transaksi pengajuan seller berhasil diambil.',
            'data' => $submissions->getCollection()
                ->map(fn (FinanceTransactionDisbursement $submission): array => $this->mapSubmission($submission))
                ->values(),
            'meta' => [
                'current_page' => $submissions->currentPage(),
                'per_page' => $submissions->perPage(),
                'last_page' => $submissions->lastPage(),
                'total' => $submissions->total(),
                'from' => $submissions->firstItem(),
                'to' => $submissions->lastItem(),
            ],
        ]);
    }

    private function applyStatusFilter($query, string $status): void
    {
        match ($status) {
            'paid' => $query->where('status', FinanceTransactionDisbursement::STATUS_DISBURSED_TO_SELLER),
            'approved' => $query->where('status', FinanceTransactionDisbursement::STATUS_BUYER_PAYMENT_CONFIRMED),
            'rejected' => $query->whereHas('transaction', fn ($query) => $query->where('status', Transaction::STATUS_CANCELED)),
            default => $query->where('status', FinanceTransactionDisbursement::STATUS_PENDING_BUYER_PAYMENT),
        };
    }

    private function mapSubmission(FinanceTransactionDisbursement $submission): array
    {
        $status = $this->submissionStatus($submission);

        return [
            'id' => $submission->unique_code,
            'disbursement_id' => $submission->id,
            'store' => [
                'id' => $submission->tenant?->id,
                'name' => $submission->tenant?->name,
            ],
            'seller' => [
                'id' => $submission->seller?->id,
                'name' => $submission->seller?->name,
                'email' => $submission->seller?->email,
                'phone' => $submission->seller?->phone,
            ],
            'bank' => [
                'name' => $submission->seller?->bank_name,
                'account_holder' => $submission->seller?->bank_account_name,
                'account_number_masked' => $this->maskAccountNumber($submission->seller?->bank_account_number),
            ],
            'amount' => $submission->amount,
            'amount_label' => $this->moneyLabel($submission->amount),
            'requested_at' => $submission->created_at?->toDateString(),
            'requested_at_label' => $submission->created_at ? $this->dateLabel($submission->created_at->toDateString()) : null,
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'transaction' => [
                'id' => $submission->transaction?->id,
                'order_number' => $submission->transaction?->order_number,
                'status' => $submission->transaction?->status,
                'status_code' => $submission->transaction?->statusCode(),
            ],
            'buyer_payment_confirmed_at' => $submission->buyer_payment_confirmed_at?->toIso8601String(),
            'disbursed_at' => $submission->disbursed_at?->toIso8601String(),
        ];
    }

    private function submissionStatus(FinanceTransactionDisbursement $submission): string
    {
        if ($submission->transaction?->status === Transaction::STATUS_CANCELED) {
            return 'rejected';
        }

        return match ($submission->status) {
            FinanceTransactionDisbursement::STATUS_DISBURSED_TO_SELLER => 'paid',
            FinanceTransactionDisbursement::STATUS_BUYER_PAYMENT_CONFIRMED => 'approved',
            default => 'requested',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'paid' => 'Berhasil',
            'approved' => 'Diproses',
            'rejected' => 'Ditolak',
            default => 'Pengajuan',
        };
    }

    private function dateLabel(string $date): string
    {
        return CarbonImmutable::parse($date)->locale('id')->translatedFormat('j M Y');
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    private function maskAccountNumber(?string $accountNumber): ?string
    {
        if (! $accountNumber) {
            return null;
        }

        return substr($accountNumber, 0, 7).'xxx';
    }
}
