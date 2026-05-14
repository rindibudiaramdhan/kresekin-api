<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAgentCommissionWithdrawalRequest;
use App\Models\AgentCommissionWithdrawal;
use App\Support\AgentCommissionCalculator;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CreateAgentCommissionWithdrawalController extends Controller
{
    public function __invoke(CreateAgentCommissionWithdrawalRequest $request, AgentCommissionCalculator $calculator): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if (! filled($user->bank_name) || ! filled($user->bank_account_name) || ! filled($user->bank_account_number)) {
            return response()->json([
                'message' => 'Lengkapi profil rekening agent sebelum mencairkan komisi.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $summary = $calculator->summary($user->id);
        $amount = (int) $validated['amount'];

        if ($amount > $summary['available_commission']) {
            return response()->json([
                'message' => 'Saldo komisi agent tidak mencukupi.',
                'data' => [
                    'available_commission' => $summary['available_commission'],
                    'available_commission_label' => $this->moneyLabel($summary['available_commission']),
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $withdrawal = AgentCommissionWithdrawal::query()->create([
            'agent_user_id' => $user->id,
            'amount' => $amount,
            'status' => AgentCommissionWithdrawal::STATUS_REQUESTED,
            'note' => $validated['note'] ?? null,
            'requested_at' => now(),
        ]);

        return response()->json([
            'message' => 'Pencairan komisi agent berhasil diajukan.',
            'data' => [
                'id' => $withdrawal->id,
                'amount' => $withdrawal->amount,
                'amount_label' => $this->moneyLabel($withdrawal->amount),
                'status' => $withdrawal->status,
                'note' => $withdrawal->note,
                'requested_at' => $withdrawal->requested_at?->toIso8601String(),
            ],
        ], Response::HTTP_CREATED);
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp. '.number_format($amount, 0, ',', '.');
    }
}
