<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidatePromoCodeRequest;
use App\Models\PromoCode;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ValidatePromoCodeController extends Controller
{
    public function __invoke(ValidatePromoCodeRequest $request): JsonResponse
    {
        $promo = PromoCode::query()
            ->available()
            ->where('code', strtoupper(trim($request->validated('code'))))
            ->first();

        if (! $promo) {
            return response()->json([
                'message' => 'Promo tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Promo berhasil ditemukan.',
            'data' => [
                'id' => $promo->id,
                'code' => $promo->code,
                'name' => $promo->name,
                'description' => $promo->description,
                'discount_type' => $promo->discount_type,
                'discount_value' => $promo->discount_value,
                'discount_label' => $this->discountLabel($promo),
                'minimum_order_amount' => $promo->minimum_order_amount,
                'minimum_order_amount_label' => $this->nullableMoneyLabel($promo->minimum_order_amount),
                'maximum_discount_amount' => $promo->maximum_discount_amount,
                'maximum_discount_amount_label' => $this->nullableMoneyLabel($promo->maximum_discount_amount),
                'quantity' => $promo->quantity,
                'used_quantity' => $promo->used_quantity,
                'remaining_quantity' => $promo->remainingQuantity(),
                'starts_at' => $promo->starts_at?->toIso8601String(),
                'ends_at' => $promo->ends_at?->toIso8601String(),
                'is_active' => $promo->is_active,
            ],
        ]);
    }

    private function discountLabel(PromoCode $promo): string
    {
        if ($promo->discount_type === PromoCode::DISCOUNT_TYPE_PERCENTAGE) {
            return $promo->discount_value.'%';
        }

        return $this->moneyLabel($promo->discount_value);
    }

    private function nullableMoneyLabel(?int $amount): ?string
    {
        if ($amount === null) {
            return null;
        }

        return $this->moneyLabel($amount);
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
