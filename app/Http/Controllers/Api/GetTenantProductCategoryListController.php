<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class GetTenantProductCategoryListController extends Controller
{
    public function __invoke(string $tenantId): JsonResponse
    {
        $tenant = Str::isUuid($tenantId)
            ? Tenant::query()->find($tenantId)
            : null;

        if (! $tenant) {
            return response()->json([
                'message' => 'Merchant tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        $categoryNames = $tenant->products()
            ->where('is_active', true)
            ->select('category')
            ->distinct()
            ->pluck('category');

        $categories = ProductCategory::query()
            ->whereIn('name', $categoryNames)
            ->orderBy('name')
            ->get()
            ->unique('name')
            ->map(fn (ProductCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'image_path' => $category->image_path,
                'image_url' => asset($category->image_path),
            ])
            ->values();

        return response()->json([
            'message' => 'Daftar kategori barang merchant berhasil diambil.',
            'data' => $categories,
        ]);
    }
}
