<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSessionToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutUserController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $plainTextToken = $request->bearerToken();

        if ($plainTextToken) {
            UserSessionToken::query()
                ->where('user_id', $request->user()->id)
                ->where('token', hash('sha256', $plainTextToken))
                ->delete();
        }

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }
}
