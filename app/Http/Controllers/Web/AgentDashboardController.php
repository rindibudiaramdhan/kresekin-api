<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\View\View;

class AgentDashboardController extends Controller
{
    public function __invoke(): View
    {
        $agentId = auth()->id();

        return view('agent.dashboard', [
            'agentName' => auth()->user()?->name,
            'agentEmail' => auth()->user()?->email,
            'tenantCount' => Tenant::query()->where('agent_user_id', $agentId)->count(),
            'productCount' => Product::query()
                ->whereHas('tenant', fn ($query) => $query->where('agent_user_id', $agentId))
                ->count(),
            'recentTenants' => Tenant::query()->where('agent_user_id', $agentId)->latest()->limit(5)->get(),
            'recentProducts' => Product::query()
                ->with('tenant')
                ->whereHas('tenant', fn ($query) => $query->where('agent_user_id', $agentId))
                ->latest()
                ->limit(5)
                ->get(),
            'agentId' => $agentId,
        ]);
    }
}
