<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OwnerStoreMonitoringRequest;
use App\Support\OwnerMonitoring\OwnerMonitoringAggregator;
use App\Support\OwnerMonitoring\OwnerMonitoringContext;
use Illuminate\Http\JsonResponse;

class GetOwnerMonitoringStoreListController extends Controller
{
    public function __invoke(OwnerStoreMonitoringRequest $request, OwnerMonitoringAggregator $aggregator): JsonResponse
    {
        $validated = $request->validated();
        $context = OwnerMonitoringContext::from($request->user(), $validated);
        $paginator = $aggregator->stores($context, $validated);

        return response()->json([
            'message' => 'Performa toko berhasil diambil.',
            'generated_at' => now('Asia/Jakarta')->toIso8601String(),
            'refresh_after_seconds' => 10,
            'data' => $paginator->items(),
            'meta' => $this->meta($paginator),
            'links' => $this->links($paginator),
        ]);
    }

    private function meta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    private function links($paginator): array
    {
        return [
            'first' => $paginator->url(1),
            'last' => $paginator->url($paginator->lastPage()),
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ];
    }
}
