<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OwnerOrderMonitoringRequest;
use App\Support\OwnerMonitoring\OwnerMonitoringAggregator;
use App\Support\OwnerMonitoring\OwnerMonitoringContext;
use Illuminate\Http\JsonResponse;

class GetOwnerMonitoringOrderListController extends Controller
{
    public function __invoke(OwnerOrderMonitoringRequest $request, OwnerMonitoringAggregator $aggregator): JsonResponse
    {
        $validated = $request->validated();
        $context = OwnerMonitoringContext::from($request->user(), $validated);
        $paginator = $aggregator->orders($context, $validated);

        return response()->json([
            'message' => 'Daftar order monitoring berhasil diambil.',
            'generated_at' => now('Asia/Jakarta')->toIso8601String(),
            'refresh_after_seconds' => 10,
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
