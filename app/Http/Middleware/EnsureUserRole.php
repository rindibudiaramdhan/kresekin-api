<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if ($request->user()?->role !== $role) {
            return $this->forbiddenResponse($role);
        }

        return $next($request);
    }

    private function forbiddenResponse(string $role): JsonResponse
    {
        return response()->json([
            'message' => sprintf('Endpoint ini hanya dapat diakses oleh user dengan role %s.', $role),
        ], Response::HTTP_FORBIDDEN);
    }
}
