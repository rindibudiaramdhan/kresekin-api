<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentCommissionWithdrawal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class FinanceCommissionWithdrawalController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([
                AgentCommissionWithdrawal::STATUS_REQUESTED,
                AgentCommissionWithdrawal::STATUS_APPROVED,
                AgentCommissionWithdrawal::STATUS_PAID,
                AgentCommissionWithdrawal::STATUS_REJECTED,
            ])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $baseQuery = fn (): Builder => AgentCommissionWithdrawal::query()
            ->when($validated['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('id', 'like', '%'.$search.'%')
                        ->orWhereHas('agent', function (Builder $query) use ($search): void {
                            $query->where('name', 'like', '%'.$search.'%')
                                ->orWhere('agent_code', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($validated['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($validated['date_from'] ?? null, fn (Builder $query, string $date) => $query->where('requested_at', '>=', $date.' 00:00:00'))
            ->when($validated['date_to'] ?? null, fn (Builder $query, string $date) => $query->where('requested_at', '<=', $date.' 23:59:59'));

        $totalDisbursed = (int) $baseQuery()
            ->where('status', AgentCommissionWithdrawal::STATUS_PAID)
            ->sum('amount');
        $totalPending = (int) $baseQuery()
            ->whereIn('status', [
                AgentCommissionWithdrawal::STATUS_REQUESTED,
                AgentCommissionWithdrawal::STATUS_APPROVED,
            ])
            ->sum('amount');
        $totalWithdrawals = $baseQuery()->count();

        return response()->json([
            'data' => [
                'total_disbursed' => $totalDisbursed,
                'total_disbursed_label' => $this->moneyLabel($totalDisbursed),
                'total_pending' => $totalPending,
                'total_pending_label' => $this->moneyLabel($totalPending),
                'total_withdrawals' => $totalWithdrawals,
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([
                AgentCommissionWithdrawal::STATUS_REQUESTED,
                AgentCommissionWithdrawal::STATUS_APPROVED,
                AgentCommissionWithdrawal::STATUS_PAID,
                AgentCommissionWithdrawal::STATUS_REJECTED,
            ])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $withdrawals = AgentCommissionWithdrawal::query()
            ->with(['agent', 'approvedBy', 'rejectedBy', 'paidBy'])
            ->when($validated['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('id', 'like', '%'.$search.'%')
                        ->orWhereHas('agent', function (Builder $query) use ($search): void {
                            $query->where('name', 'like', '%'.$search.'%')
                                ->orWhere('agent_code', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($validated['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($validated['date_from'] ?? null, fn (Builder $query, string $date) => $query->where('requested_at', '>=', $date.' 00:00:00'))
            ->when($validated['date_to'] ?? null, fn (Builder $query, string $date) => $query->where('requested_at', '<=', $date.' 23:59:59'))
            ->orderByDesc('requested_at')
            ->orderByDesc('created_at')
            ->paginate((int) ($validated['per_page'] ?? 10));

        return response()->json([
            'data' => $withdrawals->getCollection()
                ->map(fn (AgentCommissionWithdrawal $withdrawal): array => $this->mapWithdrawal($withdrawal))
                ->values(),
            'meta' => [
                'current_page' => $withdrawals->currentPage(),
                'per_page' => $withdrawals->perPage(),
                'last_page' => $withdrawals->lastPage(),
                'total' => $withdrawals->total(),
                'from' => $withdrawals->firstItem(),
                'to' => $withdrawals->lastItem(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json([
            'data' => $this->mapWithdrawal($this->findWithdrawal($id)),
        ]);
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $withdrawal = DB::transaction(function () use ($request, $id): AgentCommissionWithdrawal {
            $withdrawal = $this->findWithdrawalForUpdate($id);

            if ($withdrawal->status !== AgentCommissionWithdrawal::STATUS_REQUESTED) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Hanya pengajuan pencairan yang dapat disetujui.');
            }

            $withdrawal->forceFill([
                'status' => AgentCommissionWithdrawal::STATUS_APPROVED,
                'approved_by_user_id' => $request->user()->id,
                'approved_at' => now(),
                'processed_at' => now(),
            ])->save();

            return $withdrawal;
        });

        return response()->json([
            'message' => 'Pengajuan pencairan dana berhasil disetujui.',
            'data' => $this->mapWithdrawal($withdrawal->refresh()->load(['agent', 'approvedBy', 'rejectedBy', 'paidBy'])),
        ]);
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', Rule::in(AgentCommissionWithdrawal::rejectionReasons())],
        ]);

        $withdrawal = DB::transaction(function () use ($request, $id, $validated): AgentCommissionWithdrawal {
            $withdrawal = $this->findWithdrawalForUpdate($id);

            if ($withdrawal->status !== AgentCommissionWithdrawal::STATUS_REQUESTED) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Hanya pengajuan pencairan yang dapat ditolak.');
            }

            $withdrawal->forceFill([
                'status' => AgentCommissionWithdrawal::STATUS_REJECTED,
                'rejection_reason' => $validated['reason'],
                'rejected_by_user_id' => $request->user()->id,
                'rejected_at' => now(),
                'processed_at' => now(),
            ])->save();

            return $withdrawal;
        });

        return response()->json([
            'message' => 'Pengajuan pencairan dana berhasil ditolak.',
            'data' => $this->mapWithdrawal($withdrawal->refresh()->load(['agent', 'approvedBy', 'rejectedBy', 'paidBy'])),
        ]);
    }

    public function markAsPaid(Request $request, string $id): JsonResponse
    {
        $withdrawal = DB::transaction(function () use ($request, $id): AgentCommissionWithdrawal {
            $withdrawal = $this->findWithdrawalForUpdate($id);

            if ($withdrawal->status !== AgentCommissionWithdrawal::STATUS_APPROVED) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Hanya pencairan yang sedang diproses yang dapat diselesaikan.');
            }

            $withdrawal->forceFill([
                'status' => AgentCommissionWithdrawal::STATUS_PAID,
                'paid_by_user_id' => $request->user()->id,
                'paid_at' => now(),
                'processed_at' => now(),
            ])->save();

            return $withdrawal;
        });

        return response()->json([
            'message' => 'Pencairan dana berhasil diselesaikan.',
            'data' => $this->mapWithdrawal($withdrawal->refresh()->load(['agent', 'approvedBy', 'rejectedBy', 'paidBy'])),
        ]);
    }

    private function findWithdrawal(string $id): AgentCommissionWithdrawal
    {
        return AgentCommissionWithdrawal::query()
            ->with(['agent', 'approvedBy', 'rejectedBy', 'paidBy'])
            ->findOrFail($id);
    }

    private function findWithdrawalForUpdate(string $id): AgentCommissionWithdrawal
    {
        return AgentCommissionWithdrawal::query()
            ->lockForUpdate()
            ->findOrFail($id);
    }

    private function mapWithdrawal(AgentCommissionWithdrawal $withdrawal): array
    {
        $requestedAt = $withdrawal->requested_at?->timezone('Asia/Jakarta');
        $processedBy = match ($withdrawal->status) {
            AgentCommissionWithdrawal::STATUS_APPROVED => $withdrawal->approvedBy,
            AgentCommissionWithdrawal::STATUS_PAID => $withdrawal->paidBy,
            AgentCommissionWithdrawal::STATUS_REJECTED => $withdrawal->rejectedBy,
            default => null,
        };

        return [
            'id' => $withdrawal->id,
            'agent' => [
                'id' => $withdrawal->agent?->id,
                'name' => $withdrawal->agent?->name,
                'agent_code' => $withdrawal->agent?->agent_code,
            ],
            'bank' => [
                'name' => $withdrawal->agent?->bank_name,
                'account_number_masked' => $this->maskAccountNumber($withdrawal->agent?->bank_account_number),
                'account_holder' => $withdrawal->agent?->bank_account_name,
            ],
            'amount' => $withdrawal->amount,
            'amount_label' => $this->moneyLabel((int) $withdrawal->amount),
            'requested_at' => $withdrawal->requested_at?->toIso8601String(),
            'requested_at_label' => $requestedAt?->translatedFormat('d M Y'),
            'status' => $withdrawal->status,
            'status_label' => $this->statusLabel($withdrawal->status),
            'rejection' => $withdrawal->status === AgentCommissionWithdrawal::STATUS_REJECTED ? [
                'reason' => $withdrawal->rejection_reason,
                'reason_label' => $this->rejectionReasonLabel($withdrawal->rejection_reason),
                'rejected_at' => $withdrawal->rejected_at?->toIso8601String(),
                'rejected_at_label' => $withdrawal->rejected_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i'),
                'rejected_by' => [
                    'id' => $withdrawal->rejectedBy?->id,
                    'name' => $withdrawal->rejectedBy?->name,
                ],
            ] : null,
            'processed_by' => $processedBy ? [
                'id' => $processedBy->id,
                'name' => $processedBy->name,
            ] : null,
            'processed_at' => $withdrawal->processed_at?->toIso8601String(),
            'approved_at' => $withdrawal->approved_at?->toIso8601String(),
            'paid_at' => $withdrawal->paid_at?->toIso8601String(),
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            AgentCommissionWithdrawal::STATUS_APPROVED => 'Diproses',
            AgentCommissionWithdrawal::STATUS_PAID => 'Berhasil',
            AgentCommissionWithdrawal::STATUS_REJECTED => 'Ditolak',
            default => 'Pengajuan',
        };
    }

    private function rejectionReasonLabel(?string $reason): ?string
    {
        return match ($reason) {
            AgentCommissionWithdrawal::REJECTION_INVALID_ACCOUNT => 'Data rekening tidak valid',
            AgentCommissionWithdrawal::REJECTION_INCOMPLETE_ACCOUNT => 'Data akun belum lengkap',
            AgentCommissionWithdrawal::REJECTION_SUSPICIOUS_REQUEST => 'Pengajuan terindikasi mencurigakan',
            null => null,
            default => $reason,
        };
    }

    private function maskAccountNumber(?string $accountNumber): ?string
    {
        if (! filled($accountNumber)) {
            return null;
        }

        $visible = substr($accountNumber, 0, min(7, strlen($accountNumber)));

        return $visible.'xxx';
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
