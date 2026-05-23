<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSellerTenantRequest;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CreateSellerTenantController extends Controller
{
    public function __invoke(CreateSellerTenantRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $seller = $request->user();
        $agent = User::query()
            ->where('role', User::ROLE_AGENT)
            ->where('agent_code', $validated['agent_code'])
            ->firstOrFail();
        $category = ProductCategory::query()->findOrFail($validated['category_id']);

        $seller->forceFill([
            'name' => $validated['owner_name'],
            'phone' => $validated['owner_phone'] ?? $seller->phone,
            'email' => $validated['owner_email'] ?? $seller->email,
        ])->save();

        $tenant = Tenant::query()->create([
            'owner_user_id' => $seller->id,
            'agent_user_id' => $agent->id,
            'name' => $validated['name'],
            'category' => $category->name,
            'product_category_id' => $category->id,
            'location' => $validated['location'],
            'profile_picture_url' => $validated['profile_picture_url'] ?? null,
            'rating' => $validated['rating'] ?? 0,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'open_time' => $validated['open_time'] ?? null,
            'close_time' => $validated['close_time'] ?? null,
        ]);

        $tenant->housingAreas()->sync($validated['housing_area_ids']);
        $tenant->load(['agent', 'housingAreas', 'owner', 'productCategory']);

        return response()->json([
            'message' => 'Tenant seller berhasil dibuat.',
            'data' => [
                'id' => $tenant->id,
                'owner_user_id' => $tenant->owner_user_id,
                'agent_user_id' => $tenant->agent_user_id,
                'agent_code' => $tenant->agent?->agent_code,
                'owner' => [
                    'id' => $tenant->owner?->id,
                    'name' => $tenant->owner?->name,
                    'phone' => $tenant->owner?->phone,
                    'email' => $tenant->owner?->email,
                ],
                'name' => $tenant->name,
                'category_id' => $tenant->product_category_id,
                'category' => $tenant->category,
                'category_master' => [
                    'id' => $tenant->productCategory?->id,
                    'name' => $tenant->productCategory?->name,
                    'slug' => $tenant->productCategory?->slug,
                ],
                'location' => $tenant->location,
                'housing_areas' => $tenant->housingAreas
                    ->map(fn ($housingArea): array => [
                        'id' => $housingArea->id,
                        'name' => $housingArea->name,
                        'code' => $housingArea->code,
                        'village_code' => $housingArea->village_code,
                    ])
                    ->values(),
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
