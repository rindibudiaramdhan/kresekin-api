<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancellationReasonCategoryRequest;
use App\Models\CancellationReasonCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class UpdateFinanceCancellationReasonCategoryController extends Controller
{
    public function __invoke(CancellationReasonCategoryRequest $request, int $id): JsonResponse
    {
        $category = CancellationReasonCategory::query()->find($id);

        if (! $category) {
            return response()->json([
                'message' => 'Kategori alasan pembatalan tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validated();

        if ($category->is_system && (($validated['is_active'] ?? true) === false)) {
            throw ValidationException::withMessages([
                'is_active' => ['Kategori sistem Alasan Lainnya harus tetap aktif.'],
            ]);
        }

        $category->update([
            'name' => $category->is_system ? $category->name : $validated['name'],
            'sort_order' => $validated['sort_order'] ?? $category->sort_order,
            'allows_free_text' => $category->is_system ? true : ($validated['allows_free_text'] ?? $category->allows_free_text),
            'is_active' => $category->is_system ? true : ($validated['is_active'] ?? $category->is_active),
        ]);

        return response()->json([
            'message' => 'Kategori alasan pembatalan berhasil diperbarui.',
            'data' => $category,
        ]);
    }
}
