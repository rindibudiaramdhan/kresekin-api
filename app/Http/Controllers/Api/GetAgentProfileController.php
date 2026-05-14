<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\AgentCommissionCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetAgentProfileController extends Controller
{
    public function __invoke(Request $request, AgentCommissionCalculator $calculator): JsonResponse
    {
        $user = $request->user();
        $summary = $calculator->summary($user->id);

        return response()->json([
            'message' => 'Profil agent berhasil diambil.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'type' => $user->type,
                'role' => $user->role,
                'bank_name' => $user->bank_name,
                'bank_account_name' => $user->bank_account_name,
                'bank_account_number' => $user->bank_account_number,
                'payout_profile_completed' => filled($user->bank_name) && filled($user->bank_account_name) && filled($user->bank_account_number),
                'available_commission' => $summary['available_commission'],
                'available_commission_label' => $this->moneyLabel($summary['available_commission']),
            ],
        ]);
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp. '.number_format($amount, 0, ',', '.');
    }
}
