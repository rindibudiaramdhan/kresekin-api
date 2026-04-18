<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSellerTenantRequest;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CreateSellerTenantController extends Controller
{
    public function __invoke(CreateSellerTenantRequest $request): JsonResponse
    {
        $tenant = Tenant::query()->create([
            ...$request->validated(),
            'owner_user_id' => $request->user()->id,
            'rating' => $request->validated()['rating'] ?? 0,
        ]);

        return response()->json([
            'message' => 'Tenant seller berhasil dibuat.',
            'data' => [
                'id' => $tenant->id,
                'owner_user_id' => $tenant->owner_user_id,
                'name' => $tenant->name,
                'category' => $tenant->category,
                'profile_picture_url' => $tenant->profile_picture_url,
                'rating' => $tenant->rating,
                'latitude' => $tenant->latitude,
                'longitude' => $tenant->longitude,
                'open_time' => $tenant->open_time,
                'close_time' => $tenant->close_time,
            ],
        ], Response::HTTP_CREATED);
    }
}
