<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserDeviceRequest;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RegisterUserDeviceController extends Controller
{
    public function __invoke(RegisterUserDeviceRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $device = UserDevice::query()->firstOrNew([
            'device_token' => $validated['device_token'],
        ]);

        $device->fill([
            'user_id' => $user->id,
            'platform' => $validated['platform'],
            'device_name' => $validated['device_name'] ?? null,
            'last_seen_at' => now(),
        ]);
        $device->save();

        return response()->json([
            'message' => 'Perangkat pengguna berhasil didaftarkan.',
            'data' => [
                'id' => $device->id,
                'user_id' => $device->user_id,
                'device_token' => $device->device_token,
                'platform' => $device->platform,
                'device_name' => $device->device_name,
                'last_seen_at' => $device->last_seen_at?->toIso8601String(),
            ],
        ], $device->wasRecentlyCreated ? Response::HTTP_CREATED : Response::HTTP_OK);
    }
}
