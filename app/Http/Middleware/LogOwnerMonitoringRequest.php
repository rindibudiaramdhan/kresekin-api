<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogOwnerMonitoringRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            Log::error('owner_monitoring_request_failed', $this->context($request, $startedAt, 500));

            throw $exception;
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        if ($response->getStatusCode() >= 400 || $durationMs >= 1000) {
            Log::warning('owner_monitoring_request_attention', [
                ...$this->context($request, $startedAt, $response->getStatusCode()),
                'duration_ms' => $durationMs,
            ]);
        }

        return $response;
    }

    private function context(Request $request, float $startedAt, int $status): array
    {
        return [
            'owner_user_id' => $request->user()?->id,
            'route' => $request->route()?->uri(),
            'status' => $status,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'request_id' => $request->attributes->get('request_id'),
        ];
    }
}
