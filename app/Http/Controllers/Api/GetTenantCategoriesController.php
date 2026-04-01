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
                ->map(fn (string $category, int $index): array => [
                    'id' => $index + 1,
                    'name' => $category,
                    'slug' => str($category)->slug()->toString(),
                ])
                ->values(),
        ]);
    }
}
