<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetSellerTenantListController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $currentTime = now('Asia/Jakarta')->format('H:i');

        $tenants = $request->user()
            ->ownedTenants()
            ->latest()
            ->get()
            ->map(function ($tenant) use ($currentTime): array {
                $isOpen = $tenant->isOpenAt($currentTime);

                return [
                    'id' => $tenant->id,
                    'owner_user_id' => $tenant->owner_user_id,
                    'name' => $tenant->name,
                    'category' => $tenant->category,
                    'profile_picture_url' => $tenant->profile_picture_url,
                    'rating' => $tenant->rating,
                    'latitude' => $tenant->latitude,
                    'longitude' => $tenant->longitude,
                    'is_open' => $isOpen,
                    'store_status' => $isOpen ? 'Buka' : 'Tutup',
                    'open_time' => $tenant->open_time,
                    'close_time' => $tenant->close_time,
                    'operating_hours_label' => $tenant->operatingHoursLabel(),
                ];
            })
            ->values();

        return response()->json([
            'message' => 'Daftar tenant seller berhasil diambil.',
            'data' => $tenants,
        ]);
    }
}
