<?php

namespace App\Http\Controllers\Api;

use App\Contracts\WhatsappOtpSender;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Models\User;
use App\Notifications\LoginOtpNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class LoginUserController extends Controller
{
    public function __invoke(LoginUserRequest $request, WhatsappOtpSender $whatsappOtpSender): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()
            ->when(
                $validated['type'] === 'email',
                fn ($query) => $query->where('email', $validated['email']),
                fn ($query) => $query->where('phone', $validated['phone'])
            )
            ->first();

        if (! $user) {
            return response()->json([
                'message' => 'User tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        $otp = (string) random_int(100000, 999999);

        $user->forceFill([
            'otp_code' => Hash::make($otp),
            'otp_sent_at' => now(),
        ])->save();

        if ($validated['type'] === 'email') {
            $user->notify(new LoginOtpNotification($otp));
        } else {
            $whatsappOtpSender->send($user->phone, $otp);
        }

        return response()->json([
            'message' => 'OTP login berhasil dikirim.',
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'phone' => $user->phone,
                'type' => $user->type,
                'otp_sent_at' => $user->otp_sent_at?->toIso8601String(),
            ],
        ]);
    }
}
