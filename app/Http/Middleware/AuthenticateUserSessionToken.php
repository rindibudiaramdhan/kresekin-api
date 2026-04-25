<?php

namespace App\Http\Middleware;

use App\Models\UserSessionToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateUserSessionToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = $request->bearerToken();

        if (! $plainTextToken) {
            return $this->unauthorizedResponse();
        }

        $sessionToken = UserSessionToken::query()
            ->where('token', hash('sha256', $plainTextToken))
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with('user')
            ->first();

        if (! $sessionToken || ! $sessionToken->user) {
            return $this->unauthorizedResponse();
        }

        $sessionToken->forceFill([
            'last_used_at' => now(),
        ])->save();

        $request->setUserResolver(fn () => $sessionToken->user);

        return $next($request);
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Tidak terautentikasi.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
