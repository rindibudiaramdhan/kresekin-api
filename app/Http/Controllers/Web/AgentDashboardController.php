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
            'totalUsers' => User::query()->count(),
            'buyerCount' => User::query()->where('role', User::ROLE_BUYER)->count(),
            'sellerCount' => User::query()->where('role', User::ROLE_SELLER)->count(),
            'agentCount' => User::query()->where('role', User::ROLE_AGENT)->count(),
            'tenantCount' => Tenant::query()->count(),
            'productCount' => Product::query()->count(),
            'recentTenants' => Tenant::query()->latest()->limit(5)->get(),
            'recentProducts' => Product::query()->with('tenant')->latest()->limit(5)->get(),
            'agentId' => $agentId,
        ]);
    }
}
