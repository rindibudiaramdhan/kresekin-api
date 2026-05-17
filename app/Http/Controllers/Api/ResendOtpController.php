<?php

namespace App\Http\Controllers\Api;

use App\Contracts\WhatsappOtpSender;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResendOtpRequest;
use App\Models\User;
use App\Notifications\ResendOtpNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class ResendOtpController extends Controller
{
    public function __invoke(ResendOtpRequest $request, WhatsappOtpSender $whatsappOtpSender): JsonResponse
    {
        $validated = $request->validated();
        $role = $request->route('role');

        $user = User::query()
            ->where('role', $role)
            ->when(
                $validated['type'] === User::AUTH_TYPE_EMAIL,
                fn ($query) => $query->where('email', $validated['email']),
                fn ($query) => $query->where('phone', $validated['phone'])
            )
            ->first();

        if (! $user) {
            return response()->json([
                'message' => 'Pengguna tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        $otp = (string) random_int(100000, 999999);

        $user->forceFill([
            'otp_code' => Hash::make($otp),
            'otp_sent_at' => now(),
        ])->save();

        if ($validated['type'] === User::AUTH_TYPE_EMAIL) {
            $user->notify(new ResendOtpNotification($otp));
        } else {
            $whatsappOtpSender->send($user->phone, $otp);
        }

        return response()->json([
            'message' => 'OTP berhasil dikirim ulang.',
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'phone' => $user->phone,
                'type' => $user->type,
                'role' => $user->role,
                'otp_sent_at' => $user->otp_sent_at?->toIso8601String(),
            ],
        ]);
    }
}
