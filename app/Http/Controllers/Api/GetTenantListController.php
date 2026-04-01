<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class GetTenantListController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'category' => ['nullable', 'string', Rule::in(Tenant::CATEGORIES)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $validator->validated();
        $limit = (int) ($validated['limit'] ?? 10);
        $page = (int) ($validated['page'] ?? 1);
        $user = $request->user();

        $tenantItems = Tenant::query()
            ->when(
                isset($validated['category']),
                fn ($query) => $query->where('category', $validated['category'])
            )
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Tenant $tenant) use ($user): array {
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
            })
            ->sortBy([
                fn (array $tenant): int => $tenant['distance_km'] === null ? 1 : 0,
                fn (array $tenant): float => $tenant['distance_km'] ?? INF,
                fn (array $tenant): string => $tenant['name'],
            ])
            ->values();

        $tenants = new LengthAwarePaginator(
            $tenantItems->forPage($page, $limit)->values(),
            $tenantItems->count(),
            $limit,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return response()->json([
            'message' => 'Daftar tenant berhasil diambil.',
            'data' => $tenants->items(),
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
