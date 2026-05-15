<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CancellationReasonCategory;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DeleteFinanceCancellationReasonCategoryController extends Controller
{
    public function __invoke(int $id): JsonResponse
    {
        $category = CancellationReasonCategory::query()->find($id);

        if (! $category) {
            return response()->json([
                'message' => 'Kategori alasan pembatalan tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($category->is_system) {
            return response()->json([
                'message' => 'Kategori sistem Alasan Lainnya tidak dapat dihapus.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $category->delete();

        return response()->json([
            'message' => 'Kategori alasan pembatalan berhasil dihapus.',
        ]);
    }
}
