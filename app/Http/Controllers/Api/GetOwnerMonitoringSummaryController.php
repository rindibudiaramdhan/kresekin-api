<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OwnerMonitoringRequest;
use App\Support\OwnerMonitoring\OwnerMonitoringAggregator;
use App\Support\OwnerMonitoring\OwnerMonitoringContext;
use Illuminate\Http\JsonResponse;

class GetOwnerMonitoringSummaryController extends Controller
{
    public function __invoke(OwnerMonitoringRequest $request, OwnerMonitoringAggregator $aggregator): JsonResponse
    {
        $context = OwnerMonitoringContext::from($request->user(), $request->validated());

        return response()->json([
            'message' => 'Ringkasan online monitoring berhasil diambil.',
            'data' => $aggregator->summary($context),
        ]);
    }
}
