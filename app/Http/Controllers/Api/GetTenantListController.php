<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetTenantListController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->integer('limit', 10), 100));
        $user = $request->user();

        $tenants = Tenant::query()
            ->orderByDesc('created_at')
            ->paginate($limit);

        return response()->json([
            'message' => 'Daftar tenant berhasil diambil.',
            'data' => $tenants->getCollection()->map(function (Tenant $tenant) use ($user): array {
                $distanceKm = $this->calculateDistanceKm(
                    $user->latitude,
                    $user->longitude,
                    $tenant->latitude,
                    $tenant->longitude,
                );

                return [
                    'id' => $tenant->id,
                    'profile_picture_url' => $tenant->profile_picture_url,
                    'name' => $tenant->name,
                    'distance_km' => $distanceKm,
                    'distance_label' => $this->formatDistanceLabel($distanceKm),
                    'rating' => round((float) $tenant->rating, 1),
                    'category' => $tenant->category,
                ];
            })->values(),
            'meta' => [
                'current_page' => $tenants->currentPage(),
                'per_page' => $tenants->perPage(),
                'last_page' => $tenants->lastPage(),
                'total' => $tenants->total(),
                'from' => $tenants->firstItem(),
                'to' => $tenants->lastItem(),
            ],
            'links' => [
                'first' => $tenants->url(1),
                'last' => $tenants->url($tenants->lastPage()),
                'prev' => $tenants->previousPageUrl(),
                'next' => $tenants->nextPageUrl(),
            ],
        ]);
    }

    private function calculateDistanceKm(
        ?float $userLatitude,
        ?float $userLongitude,
        ?float $tenantLatitude,
        ?float $tenantLongitude
    ): ?float {
        if ($userLatitude === null || $userLongitude === null || $tenantLatitude === null || $tenantLongitude === null) {
            return null;
        }

        $earthRadiusKm = 6371;
        $latitudeDelta = deg2rad($tenantLatitude - $userLatitude);
        $longitudeDelta = deg2rad($tenantLongitude - $userLongitude);
        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($userLatitude)) * cos(deg2rad($tenantLatitude)) * sin($longitudeDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 1);
    }

    private function formatDistanceLabel(?float $distanceKm): ?string
    {
        return $distanceKm === null ? null : number_format($distanceKm, 1).' km';
    }
}
