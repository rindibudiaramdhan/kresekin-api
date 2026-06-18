<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Dashboard\AgentManagedUmkmPerformanceQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetAgentManagedUmkmPerformanceController extends Controller
{
    public function __invoke(Request $request, AgentManagedUmkmPerformanceQuery $performance): JsonResponse
    {
        $paginator = $performance->forRequest($request->user(), $request);

        return response()->json([
            'message' => 'Performa UMKM binaan berhasil diambil.',
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }
}
