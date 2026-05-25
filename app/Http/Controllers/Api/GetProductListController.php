<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class GetProductListController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'category' => ['nullable', 'string', 'exists:product_categories,slug'],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'is_promo' => ['nullable', 'in:true,false,1,0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data yang diberikan tidak valid.',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $validator->validated();
        $limit = (int) ($validated['limit'] ?? 10);
        $isPromo = isset($validated['is_promo'])
            ? filter_var($validated['is_promo'], FILTER_VALIDATE_BOOLEAN)
            : null;
        $productCategory = isset($validated['category'])
            ? ProductCategory::query()->where('slug', $validated['category'])->first()
            : null;

        $products = Product::query()
            ->with('tenant')
            ->where('is_active', true)
            ->when(
                $productCategory,
                fn ($query) => $query->where('category', $productCategory->name)
            )
            ->when(
                isset($validated['tenant_id']),
                fn ($query) => $query->where('tenant_id', $validated['tenant_id'])
            )
            ->when(
                isset($validated['name']),
                fn ($query) => $query->where('name', 'like', '%'.$validated['name'].'%')
            )
            ->when(
                $isPromo !== null,
                fn ($query) => $isPromo
                    ? $query->whereNotNull('original_price')->whereColumn('original_price', '>', 'price')
                    : $query->where(function ($query): void {
                        $query
                            ->whereNull('original_price')
                            ->orWhereColumn('original_price', '<=', 'price');
                    })
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
                    'image_url' => $product->publicImageUrl(),
                    'price' => $product->price,
                    'price_label' => $this->moneyLabel($product->price),
                    'original_price' => $product->original_price,
                    'original_price_label' => $product->original_price ? $this->moneyLabel($product->original_price) : null,
                    'discount_percentage' => $discountPercentage,
                    'discount_label' => $discountPercentage ? 'Disc '.$discountPercentage.'%' : null,
                    'stock' => $product->stock,
                    'unit' => $product->unit,
                    'minimum_stock' => $product->minimum_stock,
                    'is_low_stock' => $product->isLowStock(),
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
