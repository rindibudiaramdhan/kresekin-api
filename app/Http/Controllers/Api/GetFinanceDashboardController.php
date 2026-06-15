<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Dashboard\FinanceDashboardAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetFinanceDashboardController extends Controller
{
    public function __invoke(Request $request, FinanceDashboardAggregator $dashboard): JsonResponse
    {
        return response()->json([
            'message' => 'Dashboard finance berhasil diambil.',
            'data' => $dashboard->aggregate($request->query('period')),
        ]);
    }
}
