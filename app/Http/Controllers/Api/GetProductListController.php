<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class GetProductListController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'category' => ['nullable', 'string', Rule::in(Tenant::CATEGORIES)],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data yang diberikan tidak valid.',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $validator->validated();
        $limit = (int) ($validated['limit'] ?? 10);

        $products = Product::query()
            ->with('tenant')
            ->when(
                isset($validated['category']),
                fn ($query) => $query->where('category', $validated['category'])
            )
            ->when(
                isset($validated['tenant_id']),
                fn ($query) => $query->where('tenant_id', $validated['tenant_id'])
            )
            ->when(
                isset($validated['name']),
                fn ($query) => $query->where('name', 'like', '%'.$validated['name'].'%')
            )
            ->latest()
            ->paginate($limit)
            ->withQueryString();

        return response()->json([
            'message' => 'Daftar barang berhasil diambil.',
            'data' => $products->getCollection()->map(function (Product $product): array {
                $categoryUiMetadata = Tenant::categoryUiMetadata($product->category);
                $discountPercentage = $this->discountPercentage($product->price, $product->original_price);

                return [
                    'id' => $product->id,
                    'tenant_id' => $product->tenant_id,
                    'tenant_name' => $product->tenant?->name,
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
                    'discount_percentage' => $discountPercentage,
                    'discount_label' => $discountPercentage ? 'Disc '.$discountPercentage.'%' : null,
                    'weight_label' => $product->weight_label,
                ];
            })->values(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
            ],
            'links' => [
                'first' => $products->url(1),
                'last' => $products->url($products->lastPage()),
                'prev' => $products->previousPageUrl(),
                'next' => $products->nextPageUrl(),
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
}
