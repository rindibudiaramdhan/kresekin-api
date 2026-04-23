<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GetUserProfileController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $user = request()->user();

        return response()->json([
            'message' => 'Profil user berhasil diambil.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'type' => $user->type,
                'role' => $user->role,
                'housing_area_id' => $user->housingArea,
                'address' => $user->address,
                'landmark' => $user->landmark,
                'latitude' => $user->latitude,
                'longitude' => $user->longitude,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            ],
        ]);
    }
}
