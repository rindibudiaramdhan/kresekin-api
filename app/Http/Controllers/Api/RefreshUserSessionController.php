<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSessionToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RefreshUserSessionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $plainTextToken = $request->bearerToken();

        $currentSessionToken = UserSessionToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token', hash('sha256', (string) $plainTextToken))
            ->first();

        if (! $currentSessionToken) {
            return response()->json([
                'message' => 'Sesi login tidak ditemukan.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $newPlainTextToken = Str::random(64);
        $newSessionToken = UserSessionToken::query()->create([
            'user_id' => $request->user()->id,
            'token' => hash('sha256', $newPlainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $currentSessionToken->delete();

        return response()->json([
            'message' => 'Sesi login berhasil diperbarui.',
            'data' => [
                'token' => $newPlainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $newSessionToken->expires_at?->toIso8601String(),
                'user' => [
                    'id' => $request->user()->id,
                    'email' => $request->user()->email,
                    'phone' => $request->user()->phone,
                    'type' => $request->user()->type,
                    'role' => $request->user()->role,
                ],
            ],
        ]);
    }
}
