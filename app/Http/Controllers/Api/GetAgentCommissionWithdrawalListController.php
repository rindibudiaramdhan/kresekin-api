<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentCommissionWithdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetAgentCommissionWithdrawalListController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $withdrawals = AgentCommissionWithdrawal::query()
            ->where('agent_user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Daftar pencairan komisi agent berhasil diambil.',
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
            'links' => [
                'first' => $withdrawals->url(1),
                'last' => $withdrawals->url($withdrawals->lastPage()),
                'prev' => $withdrawals->previousPageUrl(),
                'next' => $withdrawals->nextPageUrl(),
            ],
        ]);
    }

    private function mapWithdrawal(AgentCommissionWithdrawal $withdrawal): array
    {
        return [
            'id' => $withdrawal->id,
            'amount' => $withdrawal->amount,
            'amount_label' => $this->moneyLabel($withdrawal->amount),
            'status' => $withdrawal->status,
            'note' => $withdrawal->note,
            'requested_at' => $withdrawal->requested_at?->toIso8601String(),
            'processed_at' => $withdrawal->processed_at?->toIso8601String(),
        ];
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp. '.number_format($amount, 0, ',', '.');
    }
}
