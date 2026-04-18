<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetSellerTenantListController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $tenants = $request->user()
            ->ownedTenants()
            ->latest()
            ->get()
            ->map(fn ($tenant) => [
                'id' => $tenant->id,
                'owner_user_id' => $tenant->owner_user_id,
                'name' => $tenant->name,
                'category' => $tenant->category,
                'profile_picture_url' => $tenant->profile_picture_url,
                'rating' => $tenant->rating,
                'latitude' => $tenant->latitude,
                'longitude' => $tenant->longitude,
                'open_time' => $tenant->open_time,
                'close_time' => $tenant->close_time,
            ])
            ->values();

        return response()->json([
            'message' => 'Daftar tenant seller berhasil diambil.',
            'data' => $tenants,
        ]);
    }
}
