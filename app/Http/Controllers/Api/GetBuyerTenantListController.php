<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
        $limit = (int) ($validated['limit'] ?? 10);
        $page = (int) ($validated['page'] ?? 1);
        $productCategory = isset($validated['product_category'])
            ? ProductCategory::query()->where('slug', $validated['product_category'])->first()
            : null;

        $tenantItems = Tenant::query()
            ->with(['products' => function ($query) use ($productCategory): void {
                $query->when(
                    $productCategory,
                    fn ($query) => $query->where('category', $productCategory->name)
                );
            }])
            ->when(
                $productCategory,
                fn ($query) => $query->whereHas('products', fn ($productQuery) => $productQuery->where('category', $productCategory->name))
            )
            ->latest()
            ->get()
            ->map(function (Tenant $tenant): array {
                $categoryUiMetadata = Tenant::categoryUiMetadata($tenant->category);

                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'profile_picture_url' => $tenant->profile_picture_url,
                    'rating' => round((float) $tenant->rating, 1),
                    'category' => $tenant->category,
                    'category_slug' => str($tenant->category)->slug()->toString(),
                    'category_icon_key' => $categoryUiMetadata['icon_key'],
                    'category_background_color' => $categoryUiMetadata['background_color'],
                    'category_icon_color' => $categoryUiMetadata['icon_color'],
                    'product_categories' => $tenant->products
                        ->pluck('category')
                        ->unique()
                        ->values()
                        ->all(),
                    'product_category_slugs' => $tenant->products
                        ->pluck('category')
                        ->unique()
                        ->map(fn (string $category) => str($category)->slug()->toString())
                        ->values()
                        ->all(),
                    'product_count' => $tenant->products->count(),
                    'open_time' => $tenant->open_time,
                    'close_time' => $tenant->close_time,
                ];
            })
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
}
