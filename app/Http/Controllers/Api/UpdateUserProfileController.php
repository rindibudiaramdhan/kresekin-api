<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserProfileRequest;
use Illuminate\Http\JsonResponse;

class UpdateUserProfileController extends Controller
{
    public function __invoke(UpdateUserProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->fill($request->validated());
        $user->save();

        return response()->json([
            'message' => 'Profil user berhasil diperbarui.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'type' => $user->type,
                'housing_area' => $user->housing_area,
                'address' => $user->address,
                'landmark' => $user->landmark,
            ],
        ]);
    }
}
