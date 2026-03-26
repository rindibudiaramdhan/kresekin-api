<?php

namespace App\Http\Controllers\Api;

use App\Contracts\WhatsappOtpSender;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use App\Notifications\RegistrationOtpNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class RegisterUserController extends Controller
{
    public function __invoke(RegisterUserRequest $request, WhatsappOtpSender $whatsappOtpSender): JsonResponse
    {
        $validated = $request->validated();
        $otp = (string) random_int(100000, 999999);

        $user = User::create([
            'name' => null,
            'email' => $validated['type'] === 'email' ? $validated['email'] : null,
            'phone' => $validated['type'] === 'phone' ? $validated['phone'] : null,
            'type' => $validated['type'],
            'password' => null,
            'otp_code' => Hash::make($otp),
            'otp_sent_at' => now(),
        ]);

        if ($user->type === 'email') {
            $user->notify(new RegistrationOtpNotification($otp));
        } else {
            $whatsappOtpSender->send($user->phone, $otp);
        }

        return response()->json([
            'message' => 'User registered successfully.',
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'phone' => $user->phone,
                'type' => $user->type,
                'otp_sent_at' => $user->otp_sent_at?->toIso8601String(),
            ],
        ], 201);
    }
}
