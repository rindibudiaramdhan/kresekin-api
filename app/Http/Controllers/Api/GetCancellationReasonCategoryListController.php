<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CancellationReasonCategory;
use Illuminate\Http\JsonResponse;

class GetCancellationReasonCategoryListController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'message' => 'Daftar kategori alasan pembatalan berhasil diambil.',
            'data' => CancellationReasonCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (CancellationReasonCategory $category): array => $this->mapCategory($category))
                ->values(),
        ]);
    }

    private function mapCategory(CancellationReasonCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'sort_order' => $category->sort_order,
            'allows_free_text' => $category->allows_free_text,
            'is_other_reason' => $category->allows_free_text && $category->name === CancellationReasonCategory::OTHER_REASON_NAME,
        ];
    }
}
