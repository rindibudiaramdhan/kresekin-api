<?php

namespace App\Http\Requests;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DeliveryMethod;
use App\Models\OrderTimeOption;
use App\Models\PaymentMethod;
use App\Models\PaymentMethodOption;
use App\Models\PromoCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpFoundation\Response;

class CheckoutRequest extends FormRequest
{
    private ?Cart $resolvedCart = null;

    private ?Collection $resolvedCartItems = null;

    private ?DeliveryMethod $resolvedDeliveryMethod = null;

    private ?PaymentMethod $resolvedPaymentMethod = null;

    private ?PaymentMethodOption $resolvedPaymentOption = null;

    private ?OrderTimeOption $resolvedOrderTimeOption = null;

    private ?PromoCode $resolvedPromoCode = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_method_code' => [
                'required',
                'string',
                Rule::exists('delivery_methods', 'code')->where('is_active', true),
            ],
            'payment_method_code' => [
                'required',
                'string',
                Rule::exists('payment_methods', 'code')->where('is_active', true),
            ],
            'payment_method_option_code' => ['nullable', 'string', 'max:50'],
            'pickup_time_option' => [
                'nullable',
                'string',
                Rule::exists('order_time_options', 'code')->where('is_active', true),
            ],
            'pickup_scheduled_at' => ['nullable', 'date_format:H:i'],
            'promo_code' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $deliveryMethod = $this->deliveryMethod();

            if ($this->cartItems()->isEmpty()) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Keranjang kosong.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            $paymentMethod = $this->paymentMethod();
            $optionCode = $this->input('payment_method_option_code');

            if ($paymentMethod->requires_option && ! $optionCode) {
                $validator->errors()->add('payment_method_option_code', 'Kolom kode opsi metode pembayaran wajib diisi.');
            }

            if ($paymentMethod->requires_option && $optionCode) {
                $validOptionCodes = $paymentMethod->activeOptions->pluck('code')->all();

                if (! in_array(strtolower(trim($optionCode)), $validOptionCodes, true)) {
                    $validator->errors()->add('payment_method_option_code', 'Kode opsi metode pembayaran yang dipilih tidak valid.');
                }
            }

            if ($deliveryMethod->requires_order_time) {
                $pickupTimeOption = $this->input('pickup_time_option');

                if (! $pickupTimeOption) {
                    $validator->errors()->add('pickup_time_option', 'Kolom opsi waktu pengambilan wajib diisi.');

                    return;
                }

                $orderTimeOption = $this->orderTimeOption();

                if ($orderTimeOption?->requires_schedule && ! $this->input('pickup_scheduled_at')) {
                    $validator->errors()->add('pickup_scheduled_at', 'Kolom jadwal waktu pengambilan wajib diisi.');
                }
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->filled('promo_code') && ! $this->promoCode()) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Promo tidak ditemukan.',
                ], Response::HTTP_NOT_FOUND));
            }

            if ($this->promoCode() && $this->subtotal() < $this->promoCode()->minimum_order_amount) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Minimal belanja untuk promo belum terpenuhi.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY));
            }
        });
    }

    public function cart(): Cart
    {
        return $this->resolvedCart ??= Cart::query()->firstOrCreate([
            'user_id' => $this->user()->id,
        ]);
    }

    public function cartItems(): Collection
    {
        return $this->resolvedCartItems ??= CartItem::query()
            ->with('product')
            ->where('user_id', $this->user()->id)
            ->get();
    }

    public function deliveryMethod(): ?DeliveryMethod
    {
        if ($this->resolvedDeliveryMethod !== null) {
            return $this->resolvedDeliveryMethod;
        }

        return $this->resolvedDeliveryMethod = DeliveryMethod::query()
            ->active()
            ->where('code', strtolower(trim((string) $this->input('delivery_method_code'))))
            ->first();
    }

    public function paymentMethod(): ?PaymentMethod
    {
        if ($this->resolvedPaymentMethod !== null) {
            return $this->resolvedPaymentMethod;
        }

        return $this->resolvedPaymentMethod = PaymentMethod::query()
            ->active()
            ->with('activeOptions')
            ->where('code', strtolower(trim((string) $this->input('payment_method_code'))))
            ->first();
    }

    public function paymentOption(): ?PaymentMethodOption
    {
        if ($this->resolvedPaymentOption !== null) {
            return $this->resolvedPaymentOption;
        }

        $paymentMethod = $this->paymentMethod();

        if (! $paymentMethod?->requires_option) {
            return null;
        }

        $optionCode = strtolower(trim((string) $this->input('payment_method_option_code')));

        return $this->resolvedPaymentOption = $paymentMethod->activeOptions
            ->first(fn (PaymentMethodOption $option): bool => $option->code === $optionCode);
    }

    public function pickupTimeOption(): ?string
    {
        return $this->deliveryMethod()?->requires_order_time
            ? ($this->validated('pickup_time_option') ?? null)
            : null;
    }

    public function orderTimeOption(): ?OrderTimeOption
    {
        if ($this->resolvedOrderTimeOption !== null) {
            return $this->resolvedOrderTimeOption;
        }

        $pickupTimeOption = $this->input('pickup_time_option');

        if (! $pickupTimeOption) {
            return null;
        }

        return $this->resolvedOrderTimeOption = OrderTimeOption::query()
            ->active()
            ->where('code', strtolower(trim($pickupTimeOption)))
            ->first();
    }

    public function pickupScheduledAt(): ?string
    {
        return $this->orderTimeOption()?->requires_schedule
            ? ($this->validated('pickup_scheduled_at') ?? null)
            : null;
    }

    public function subtotal(): int
    {
        return $this->cartItems()->sum(fn (CartItem $item): int => $item->quantity * $item->product->price);
    }

    public function deliveryFee(): int
    {
        return $this->deliveryMethod()?->fee ?? 0;
    }

    public function promoCode(): ?PromoCode
    {
        if ($this->resolvedPromoCode !== null) {
            return $this->resolvedPromoCode;
        }

        $promoCode = $this->input('promo_code');

        if (! $promoCode) {
            return null;
        }

        return $this->resolvedPromoCode = PromoCode::query()
            ->available()
            ->where('code', strtoupper(trim($promoCode)))
            ->first();
    }

    public function discountAmount(): int
    {
        $promo = $this->promoCode();

        if (! $promo) {
            return 0;
        }

        if ($promo->discount_type === PromoCode::DISCOUNT_TYPE_PERCENTAGE) {
            $discountAmount = (int) floor($this->subtotal() * $promo->discount_value / 100);

            if ($promo->maximum_discount_amount !== null) {
                $discountAmount = min($discountAmount, $promo->maximum_discount_amount);
            }

            return min($discountAmount, $this->subtotal());
        }

        return min($promo->discount_value, $this->subtotal());
    }

    public function grandTotal(): int
    {
        return max($this->subtotal() + $this->deliveryFee() - $this->discountAmount(), 0);
    }
}
