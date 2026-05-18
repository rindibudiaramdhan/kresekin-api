<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HousingArea;
use Illuminate\Http\JsonResponse;

class GetHousingAreaListController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'message' => 'Daftar area perumahan berhasil diambil.',
            'data' => HousingArea::query()
                ->where('village_code', '3273141003')
                ->orderBy('name')
                ->get()
                ->map(fn (HousingArea $area): array => [
                    'id' => $area->id,
                    'name' => $area->name,
                    'code' => $area->code,
                    'city' => $area->city,
                    'district' => $area->district,
                    'subdistrict' => $area->subdistrict,
                    'village_code' => $area->village_code,
                ])
                ->values(),
        ]);
    }
}
