<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GetProductDetailController extends Controller
{
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $product = Product::query()->with('tenant')->find($id);

        if (! $product) {
            return response()->json([
                'message' => 'Barang tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        $tenant = $product->tenant;
        $distanceKm = $tenant
            ? $this->calculateDistanceKm(
                $request->user()->latitude,
                $request->user()->longitude,
                $tenant->latitude,
                $tenant->longitude,
            )
            : null;

        $categoryUiMetadata = Tenant::categoryUiMetadata($product->category);

        return response()->json([
            'message' => 'Detail barang berhasil diambil.',
            'data' => [
                'id' => $product->id,
                'tenant_id' => $product->tenant_id,
                'tenant_name' => $tenant?->name,
                'tenant_profile_picture_url' => $tenant?->profile_picture_url,
                'name' => $product->name,
                'category' => $product->category,
                'category_slug' => str($product->category)->slug()->toString(),
                'category_icon_key' => $categoryUiMetadata['icon_key'],
                'category_background_color' => $categoryUiMetadata['background_color'],
                'category_icon_color' => $categoryUiMetadata['icon_color'],
                'image_url' => $product->image_url,
                'price' => $product->price,
                'price_label' => $this->moneyLabel($product->price),
                'original_price' => $product->original_price,
                'original_price_label' => $product->original_price ? $this->moneyLabel($product->original_price) : null,
                'discount_percentage' => $this->discountPercentage($product->price, $product->original_price),
                'weight_label' => $product->weight_label,
                'description' => $product->description,
                'delivery_estimate' => $product->delivery_estimate,
                'tenant_rating' => $tenant?->rating !== null ? round((float) $tenant->rating, 1) : null,
                'distance_km' => $distanceKm,
                'distance_label' => $distanceKm !== null ? number_format($distanceKm, 1).' km' : null,
            ],
        ]);
    }

    private function discountPercentage(int $price, ?int $originalPrice): ?int
    {
        if (! $originalPrice || $originalPrice <= 0 || $originalPrice <= $price) {
            return null;
        }

        return (int) round((($originalPrice - $price) / $originalPrice) * 100);
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    private function calculateDistanceKm(
        ?float $userLatitude,
        ?float $userLongitude,
        ?float $tenantLatitude,
        ?float $tenantLongitude
    ): ?float {
        if ($userLatitude === null || $userLongitude === null || $tenantLatitude === null || $tenantLongitude === null) {
            return null;
        }

        $earthRadiusKm = 6371;
        $latitudeDelta = deg2rad($tenantLatitude - $userLatitude);
        $longitudeDelta = deg2rad($tenantLongitude - $userLongitude);
        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($userLatitude)) * cos(deg2rad($tenantLatitude)) * sin($longitudeDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 1);
    }
}
