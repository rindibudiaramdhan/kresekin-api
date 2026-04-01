<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class GetTenantCategoriesController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'message' => 'Daftar kategori tenant berhasil diambil.',
            'data' => collect(Tenant::CATEGORIES)
                ->map(function (string $category, int $index): array {
                    $uiMetadata = Tenant::categoryUiMetadata($category);

                    return [
                        'id' => $index + 1,
                        'name' => $category,
                        'slug' => str($category)->slug()->toString(),
                        'icon_key' => $uiMetadata['icon_key'],
                        'background_color' => $uiMetadata['background_color'],
                        'icon_color' => $uiMetadata['icon_color'],
                    ];
                })
                ->values(),
        ]);
    }
}
