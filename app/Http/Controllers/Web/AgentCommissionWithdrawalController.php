<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAgentCommissionWithdrawalRequest;
use App\Models\AgentCommissionWithdrawal;
use App\Support\AgentCommissionCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentCommissionWithdrawalController extends Controller
{
    public function index(Request $request, AgentCommissionCalculator $calculator): View
    {
        $withdrawals = AgentCommissionWithdrawal::query()
            ->where('agent_user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('agent.withdrawals.index', [
            'agent' => $request->user(),
            'summary' => $calculator->summary($request->user()->id),
            'withdrawals' => $withdrawals,
        ]);
    }

    public function store(CreateAgentCommissionWithdrawalRequest $request, AgentCommissionCalculator $calculator): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if (! filled($user->bank_name) || ! filled($user->bank_account_name) || ! filled($user->bank_account_number)) {
            return back()
                ->withErrors(['amount' => 'Lengkapi profil rekening agent sebelum mencairkan komisi.'])
                ->withInput();
        }

        $summary = $calculator->summary($user->id);
        $amount = (int) $validated['amount'];

        if ($amount > $summary['available_commission']) {
            return back()
                ->withErrors(['amount' => 'Saldo komisi agent tidak mencukupi.'])
                ->withInput();
        }

        AgentCommissionWithdrawal::query()->create([
            'agent_user_id' => $user->id,
            'amount' => $amount,
            'status' => AgentCommissionWithdrawal::STATUS_REQUESTED,
            'note' => $validated['note'] ?? null,
            'requested_at' => now(),
        ]);

        return redirect()
            ->route('agent.withdrawals.index')
            ->with('status', 'Pencairan komisi berhasil diajukan.');
    }
}
