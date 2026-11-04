<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateUserProfileController extends Controller
{
    public function __invoke(UpdateUserProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $areaIds = $validated['housing_area_ids'] ?? [$validated['housing_area_id']];

        DB::transaction(function () use ($user, $validated, $areaIds): void {
            $user->fill(Arr::except($validated, ['housing_area_ids', 'housing_area_id']));
            $user->housing_area_id = $areaIds[0];
            $user->save();
            $user->housingAreas()->sync($areaIds);
        });

        $user->load(['housingArea', 'housingAreas']);

        return response()->json([
            'message' => 'Profil pengguna berhasil diperbarui.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'type' => $user->type,
                'role' => $user->role,
                'housing_area' => $user->housingArea,
                'housing_areas' => $user->profileHousingAreas()->makeHidden('pivot'),
                'address' => $user->address,
                'landmark' => $user->landmark,
            ],
        ]);
    }
}
