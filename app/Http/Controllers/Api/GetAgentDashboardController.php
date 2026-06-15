<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Dashboard\AgentDashboardAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetAgentDashboardController extends Controller
{
    public function __invoke(Request $request, AgentDashboardAggregator $dashboard): JsonResponse
    {
        return response()->json([
            'message' => 'Dashboard agent berhasil diambil.',
            'data' => $dashboard->forUser($request->user(), $request->query('period')),
        ]);
    }
}
