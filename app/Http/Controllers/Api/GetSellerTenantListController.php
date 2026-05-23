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
            ->with(['agent', 'housingAreas', 'productCategory'])
            ->latest()
            ->get()
            ->map(function ($tenant) use ($currentTime): array {
                $isOpen = $tenant->isOpenAt($currentTime);

                return [
                    'id' => $tenant->id,
                    'owner_user_id' => $tenant->owner_user_id,
                    'agent_user_id' => $tenant->agent_user_id,
                    'agent_code' => $tenant->agent?->agent_code,
                    'name' => $tenant->name,
                    'category_id' => $tenant->product_category_id,
                    'category' => $tenant->category,
                    'category_master' => [
                        'id' => $tenant->productCategory?->id,
                        'name' => $tenant->productCategory?->name,
                        'slug' => $tenant->productCategory?->slug,
                    ],
                    'location' => $tenant->location,
                    'housing_areas' => $tenant->housingAreas
                        ->map(fn ($housingArea): array => [
                            'id' => $housingArea->id,
                            'name' => $housingArea->name,
                            'code' => $housingArea->code,
                            'village_code' => $housingArea->village_code,
                        ])
                        ->values(),
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
