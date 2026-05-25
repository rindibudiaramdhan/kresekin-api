<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadSellerProductImageRequest;
use App\Support\ProductImageStorage;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UploadSellerProductImageController extends Controller
{
    public function __invoke(UploadSellerProductImageRequest $request): JsonResponse
    {
        $imageStorage = new ProductImageStorage;
        $imagePath = $imageStorage->store($request->file('image'));

        return response()->json([
            'message' => 'Gambar produk berhasil diupload.',
            'data' => [
                'image_path' => $imagePath,
                'image_url' => $imageStorage->url($imagePath),
            ],
        ], Response::HTTP_CREATED);
    }
}
