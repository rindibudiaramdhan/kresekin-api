<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;

class GetProductCategoriesController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'message' => 'Daftar kategori barang berhasil diambil.',
            'data' => ProductCategory::query()
                ->orderBy('name')
                ->get()
                ->map(function (ProductCategory $category): array {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'image_path' => $category->image_path,
                        'image_url' => asset($category->image_path),
                    ];
                })
                ->values(),
        ]);
    }
}
