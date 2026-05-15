<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAgentPayoutProfileRequest;
use App\Support\AgentCommissionCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentProfileController extends Controller
{
    public function edit(Request $request, AgentCommissionCalculator $calculator): View
    {
        return view('agent.profile.edit', [
            'agent' => $request->user(),
            'summary' => $calculator->summary($request->user()->id),
        ]);
    }

    public function update(UpdateAgentPayoutProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()
            ->route('agent.profile.edit')
            ->with('status', 'Profil pencairan berhasil diperbarui.');
    }
}
