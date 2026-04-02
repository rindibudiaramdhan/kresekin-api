<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyOtpRequest;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class VerifyOtpController extends Controller
{
    public function __invoke(VerifyOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()
            ->when(
                $validated['type'] === User::AUTH_TYPE_EMAIL,
                fn ($query) => $query->where('email', $validated['email']),
                fn ($query) => $query->where('phone', $validated['phone'])
            )
            ->first();

        if (! $user || ! $user->otp_code || ! Hash::check($validated['otp'], $user->otp_code)) {
            return response()->json([
                'message' => 'Kode OTP tidak valid.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $plainTextToken = Str::random(64);
        $sessionToken = UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $user->forceFill([
            'otp_code' => null,
            'otp_sent_at' => null,
            'email_verified_at' => $user->type === User::AUTH_TYPE_EMAIL ? now() : $user->email_verified_at,
        ])->save();

        return response()->json([
            'message' => 'OTP berhasil diverifikasi.',
            'data' => [
                'token' => $plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $sessionToken->expires_at?->toIso8601String(),
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'type' => $user->type,
                    'role' => $user->role,
                ],
            ],
        ]);
    }
}
