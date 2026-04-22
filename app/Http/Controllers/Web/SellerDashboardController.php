<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\View\View;

class SellerDashboardController extends Controller
{
    public function __invoke(): View
    {
        $sellerId = auth()->id();

        return view('seller.dashboard', [
            'tenantCount' => Tenant::query()->where('owner_user_id', $sellerId)->count(),
            'productCount' => Product::query()
                ->whereHas('tenant', fn ($query) => $query->where('owner_user_id', $sellerId))
                ->count(),
        ]);
    }
}
