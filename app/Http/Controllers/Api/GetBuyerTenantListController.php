<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class GetBuyerTenantListController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'product_category' => ['nullable', 'string', 'exists:product_categories,slug'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data yang diberikan tidak valid.',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $validator->validated();
        $user = $request->user();

        $limit = (int) ($validated['limit'] ?? 10);
        $page = (int) ($validated['page'] ?? 1);
        $productCategory = $validated['product_category'] ?? null;

        $tenants = Tenant::query()
            ->select(
                'id',
                'name',
                'profile_picture_url',
                'rating',
                'open_time',
                'close_time',
            )
            ->with([
                'housingAreas:id,name,code',
                // 'products',
            ])
            ->withCount('products')
            // ->whereHas('housingAreas', function ($query) use ($user) {
            //     $query->whereIn('housing_areas.id', [$user->housing_area_id]);
            // })
            // ->openNow()
            ->paginate($limit);

        return response()->json([
            'message' => 'Daftar tenant berhasil diambil.',
            'data' => $tenants->items(),
            'productCategory' => $productCategory,
            'user' => [
                'housing_area_id' => $user->housing_area_id,
            ],
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

        // $tenantItems = Tenant::query()
        //     ->with([
        //         'housingAreas',
        //         'products' => function ($query) use ($productCategory): void {
        //             $query->when(
        //                 $productCategory,
        //                 fn ($query) => $query->where('category', $productCategory->name)
        //             );
        //         },
        //     ])
        //     ->when(
        //         $productCategory,
        //         fn ($query) => $query->whereHas('products', fn ($productQuery) => $productQuery->where('category', $productCategory->name))
        //     )
        //     ->whereHas(
        //         'housingAreas',
        //         fn ($housingAreaQuery) => $housingAreaQuery->whereIn('housing_areas.id', $areaIds)
        //     )
        //     ->latest()
        //     ->get()
        //     ->map(function (Tenant $tenant) use ($currentTime): array {
        //         $isOpen = $tenant->isOpenAt($currentTime);

        //         return [
        //             'id' => $tenant->id,
        //             'name' => $tenant->name,
        //             'profile_picture_url' => $tenant->profile_picture_url,
        //             'rating' => round((float) $tenant->rating, 1),
        //             'housing_areas' => $tenant->housingAreas
        //                 ->map(fn ($housingArea): array => [
        //                     'id' => $housingArea->id,
        //                     'name' => $housingArea->name,
        //                     'code' => $housingArea->code,
        //                     'village_code' => $housingArea->village_code,
        //                 ])
        //                 ->values(),
        //             'product_count' => $tenant->products->count(),
        //             'is_open' => $isOpen,
        //             'store_status' => $isOpen ? 'Buka' : 'Tutup',
        //             'open_time' => $tenant->open_time,
        //             'close_time' => $tenant->close_time,
        //             'operating_hours_label' => $tenant->operatingHoursLabel(),
        //         ];
        //     })
        //     ->values();

        // $tenants = new LengthAwarePaginator(
        //     $tenantItems->forPage($page, $limit)->values(),
        //     $tenantItems->count(),
        //     $limit,
        //     $page,
        //     [
        //         'path' => $request->url(),
        //         'query' => Arr::except($request->query(), 'housing_area_id'),
        //     ]
        // );

        // return response()->json([
        //     'message' => 'Daftar tenant berhasil diambil.',
        //     'data' => $tenants->items(),
        //     'meta' => [
        //         'current_page' => $tenants->currentPage(),
        //         'per_page' => $tenants->perPage(),
        //         'last_page' => $tenants->lastPage(),
        //         'total' => $tenants->total(),
        //         'from' => $tenants->firstItem(),
        //         'to' => $tenants->lastItem(),
        //     ],
        //     'links' => [
        //         'first' => $tenants->url(1),
        //         'last' => $tenants->url($tenants->lastPage()),
        //         'prev' => $tenants->previousPageUrl(),
        //         'next' => $tenants->nextPageUrl(),
        //     ],
        // ]);
    }
}
