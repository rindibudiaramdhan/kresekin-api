<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancellationReasonCategoryRequest;
use App\Models\CancellationReasonCategory;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CreateFinanceCancellationReasonCategoryController extends Controller
{
    public function __invoke(CancellationReasonCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $category = CancellationReasonCategory::query()->create([
            'name' => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'allows_free_text' => $validated['allows_free_text'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'is_system' => false,
        ]);

        return response()->json([
            'message' => 'Kategori alasan pembatalan berhasil dibuat.',
            'data' => $category,
        ], Response::HTTP_CREATED);
    }
}
