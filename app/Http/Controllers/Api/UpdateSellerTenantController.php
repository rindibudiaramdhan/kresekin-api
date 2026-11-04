<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSellerTenantRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class UpdateSellerTenantController extends Controller
{
    public function __invoke(UpdateSellerTenantRequest $request, string $id): JsonResponse
    {
        $tenant = $request->user()->ownedTenants()->whereKey($id)->first();

        if (! $tenant) {
            return response()->json(['message' => 'Tenant tidak ditemukan.'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($tenant, $validated): void {
            $tenant->fill(Arr::except($validated, ['housing_area_ids']))->save();

            if (array_key_exists('housing_area_ids', $validated)) {
                $tenant->housingAreas()->sync($validated['housing_area_ids']);
            }
        });

        $tenant->load('housingAreas');

        return response()->json([
            'message' => 'Tenant seller berhasil diperbarui.',
            'data' => [
                'id' => $tenant->id,
                'owner_user_id' => $tenant->owner_user_id,
                'name' => $tenant->name,
                'location' => $tenant->location,
                'housing_areas' => $tenant->housingAreas->map(fn ($area): array => [
                    'id' => $area->id,
                    'name' => $area->name,
                    'code' => $area->code,
                    'village_code' => $area->village_code,
                ])->values(),
                'profile_picture_url' => $tenant->profile_picture_url,
                'latitude' => $tenant->latitude,
                'longitude' => $tenant->longitude,
                'open_time' => $tenant->open_time,
                'close_time' => $tenant->close_time,
            ],
        ]);
    }
}
