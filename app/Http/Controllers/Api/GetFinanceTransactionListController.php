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

class GetFinanceTransactionListController extends Controller
{
    public function __invoke(Request $request, FinanceDisbursementSyncer $syncer): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in([
                FinanceTransactionDisbursement::STATUS_PENDING_BUYER_PAYMENT,
                FinanceTransactionDisbursement::STATUS_BUYER_PAYMENT_CONFIRMED,
                FinanceTransactionDisbursement::STATUS_DISBURSED_TO_SELLER,
            ])],
            'transaction_status_group' => ['nullable', 'string', Rule::in(['paid', 'requested', 'approved', 'rejected'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 10);

        Transaction::query()
            ->with('items.tenant')
            ->whereDoesntHave('financeDisbursements')
            ->chunkById(100, fn ($transactions) => $transactions->each(fn (Transaction $transaction) => $syncer->syncForTransaction($transaction)));

        $disbursements = FinanceTransactionDisbursement::query()
            ->with(['transaction.user', 'tenant.owner'])
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['transaction_status_group'] ?? null, function ($query, string $statusGroup): void {
                $statuses = collect($this->transactionStatusCodesForGroup($statusGroup))
                    ->map(fn (string $statusCode): ?string => Transaction::statusFromCode($statusCode))
                    ->filter()
                    ->values()
                    ->all();

                $query->whereHas('transaction', fn ($query) => $query->whereIn('status', $statuses));
            })
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('unique_code', 'like', '%'.$search.'%')
                        ->orWhereHas('transaction', fn ($query) => $query->where('order_number', 'like', '%'.$search.'%'))
                        ->orWhereHas('transaction.user', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('tenant', fn ($query) => $query->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($validated['date_from'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($validated['date_to'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'message' => 'Daftar transaksi finance berhasil diambil.',
            'data' => $disbursements->getCollection()
                ->map(fn (FinanceTransactionDisbursement $disbursement): array => $this->mapDisbursement($disbursement))
                ->values(),
            'meta' => [
                'current_page' => $disbursements->currentPage(),
                'per_page' => $disbursements->perPage(),
                'last_page' => $disbursements->lastPage(),
                'total' => $disbursements->total(),
                'from' => $disbursements->firstItem(),
                'to' => $disbursements->lastItem(),
            ],
            'links' => [
                'first' => $disbursements->url(1),
                'last' => $disbursements->url($disbursements->lastPage()),
                'prev' => $disbursements->previousPageUrl(),
                'next' => $disbursements->nextPageUrl(),
            ],
        ]);
    }

    private function mapDisbursement(FinanceTransactionDisbursement $disbursement): array
    {
        return [
            'id' => $disbursement->id,
            'unique_code' => $disbursement->unique_code,
            'status' => $disbursement->status,
            'status_label' => $this->statusLabel($disbursement->status),
            'amount' => $disbursement->amount,
            'amount_label' => $this->moneyLabel($disbursement->amount),
            'buyer' => [
                'id' => $disbursement->transaction?->user?->id,
                'name' => $disbursement->transaction?->user?->name,
                'email' => $disbursement->transaction?->user?->email,
                'phone' => $disbursement->transaction?->user?->phone,
            ],
            'store' => [
                'id' => $disbursement->tenant?->id,
                'name' => $disbursement->tenant?->name,
            ],
            'seller' => [
                'id' => $disbursement->seller?->id,
                'name' => $disbursement->seller?->name,
                'email' => $disbursement->seller?->email,
                'phone' => $disbursement->seller?->phone,
            ],
            'bank' => [
                'name' => $disbursement->seller?->bank_name,
                'account_holder' => $disbursement->seller?->bank_account_name,
                'account_number_masked' => $this->maskAccountNumber($disbursement->seller?->bank_account_number),
            ],
            'transaction' => [
                'id' => $disbursement->transaction?->id,
                'order_number' => $disbursement->transaction?->order_number,
                'status' => $disbursement->transaction?->status,
                'status_code' => $disbursement->transaction?->statusCode(),
                'total_amount' => $disbursement->transaction?->total_amount,
                'total_amount_label' => $this->moneyLabel((int) $disbursement->transaction?->total_amount),
            ],
            'requested_at' => $disbursement->created_at?->toDateString(),
            'requested_at_label' => $disbursement->created_at
                ? CarbonImmutable::instance($disbursement->created_at)->locale('id')->translatedFormat('j M Y')
                : null,
            'buyer_payment_confirmed_at' => $disbursement->buyer_payment_confirmed_at?->toIso8601String(),
            'disbursed_at' => $disbursement->disbursed_at?->toIso8601String(),
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            FinanceTransactionDisbursement::STATUS_DISBURSED_TO_SELLER => 'Berhasil',
            FinanceTransactionDisbursement::STATUS_BUYER_PAYMENT_CONFIRMED => 'Diproses',
            default => 'Pengajuan',
        };
    }

    /**
     * @return array<int, string>
     */
    private function transactionStatusCodesForGroup(string $statusGroup): array
    {
        return match ($statusGroup) {
            'paid' => [Transaction::STATUS_CODE_COMPLETED],
            'approved' => [
                Transaction::STATUS_CODE_ACCEPTED_BY_STORE,
                Transaction::STATUS_CODE_PROCESSING,
                Transaction::STATUS_CODE_ON_THE_WAY,
            ],
            'rejected' => [Transaction::STATUS_CODE_CANCELED],
            default => [Transaction::STATUS_CODE_PENDING_PAYMENT],
        };
    }

    private function maskAccountNumber(?string $accountNumber): ?string
    {
        if (! $accountNumber) {
            return null;
        }

        return substr($accountNumber, 0, 7).'xxx';
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp. '.number_format($amount, 0, ',', '.');
    }
}
