<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DeliveryMethod;
use App\Models\OrderTimeOption;
use App\Models\PaymentMethod;
use App\Models\PaymentMethodOption;
use App\Models\PromoCode;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionStatusHistory;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController extends Controller
{
    public function __invoke(CheckoutRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $cart = Cart::query()->firstOrCreate([
            'user_id' => $user->id,
        ]);

        $deliveryMethod = $this->resolveDeliveryMethod($cart->delivery_method_code);

        if (! $deliveryMethod) {
            return response()->json([
                'message' => 'Metode pengiriman belum dipilih di keranjang.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paymentMethod = $this->resolvePaymentMethod($validated['payment_method_code']);
        $paymentOption = $this->resolvePaymentOption($paymentMethod, $validated['payment_method_option_code'] ?? null);
        $pickupTimeOption = $deliveryMethod->requires_order_time
            ? ($validated['pickup_time_option'] ?? null)
            : null;
        $orderTimeOption = $this->resolveOrderTimeOption($pickupTimeOption);
        $pickupScheduledAt = $orderTimeOption?->requires_schedule
            ? ($validated['pickup_scheduled_at'] ?? null)
            : null;

        $cartItems = CartItem::query()
            ->with('product')
            ->where('user_id', $user->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'message' => 'Keranjang kosong.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $subtotal = $cartItems->sum(fn (CartItem $item): int => $item->quantity * $item->product->price);
        $deliveryFee = $deliveryMethod->fee;
        $promo = $this->resolvePromoCode($validated['promo_code'] ?? null);

        if (($validated['promo_code'] ?? null) && ! $promo) {
            return response()->json([
                'message' => 'Promo tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($promo && $subtotal < $promo->minimum_order_amount) {
            return response()->json([
                'message' => 'Minimal belanja untuk promo belum terpenuhi.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $discountAmount = $promo ? $this->discountAmount($promo, $subtotal) : 0;
        $grandTotal = max($subtotal + $deliveryFee - $discountAmount, 0);

        $transaction = DB::transaction(function () use (
            $user,
            $cart,
            $cartItems,
            $deliveryMethod,
            $paymentMethod,
            $paymentOption,
            $pickupTimeOption,
            $pickupScheduledAt,
            $promo,
            $subtotal,
            $deliveryFee,
            $discountAmount,
            $grandTotal
        ): Transaction {
            if ($promo) {
                $promo = PromoCode::query()
                    ->available()
                    ->lockForUpdate()
                    ->find($promo->id);

                if (! $promo) {
                    throw new HttpResponseException(response()->json([
                        'message' => 'Promo tidak ditemukan.',
                    ], Response::HTTP_NOT_FOUND));
                }
            }

            $transaction = Transaction::query()->create([
                'user_id' => $user->id,
                'order_number' => $this->generateOrderNumber(),
                'status' => Transaction::STATUS_PENDING_PAYMENT,
                'subtotal_amount' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $grandTotal,
                'delivery_method' => $deliveryMethod->name,
                'delivery_method_code' => $deliveryMethod->code,
                'pickup_time_option' => $pickupTimeOption,
                'pickup_scheduled_at' => $pickupScheduledAt,
                'payment_method' => $paymentMethod->name,
                'payment_method_code' => $paymentMethod->code,
                'payment_method_option_code' => $paymentOption?->code,
                'payment_method_option_name' => $paymentOption?->name,
                'promo_code_id' => $promo?->id,
                'promo_code' => $promo?->code,
                'promo_name' => $promo?->name,
                'promo_discount_type' => $promo?->discount_type,
                'promo_discount_value' => $promo?->discount_value,
                'discount_amount' => $discountAmount,
                'transaction_at' => now(),
            ]);

            foreach ($cartItems as $cartItem) {
                TransactionItem::query()->create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $cartItem->product_id,
                    'tenant_id' => $cartItem->product->tenant_id,
                    'product_name' => $cartItem->product->name,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->product->price,
                    'line_total' => $cartItem->quantity * $cartItem->product->price,
                ]);
            }

            TransactionStatusHistory::query()->create([
                'transaction_id' => $transaction->id,
                'status' => Transaction::STATUS_PENDING_PAYMENT,
                'title' => $this->paymentStatusTitle($paymentMethod, $paymentOption),
                'description' => 'Menunggu pembayaran dari user',
                'sequence' => 1,
                'status_at' => now(),
            ]);

            CartItem::query()
                ->where('user_id', $user->id)
                ->delete();

            $cart->forceFill([
                'delivery_method_code' => null,
            ])->save();

            if ($promo) {
                $promo->increment('used_quantity');
            }

            return $transaction->fresh();
        });

        return response()->json([
            'message' => 'Checkout berhasil dibuat.',
            'data' => [
                'transaction_id' => $transaction->id,
                'order_number' => $transaction->order_number,
                'status' => $transaction->status,
                'subtotal_amount' => $transaction->subtotal_amount,
                'subtotal_amount_label' => $this->moneyLabel($transaction->subtotal_amount),
                'delivery_fee' => $transaction->delivery_fee,
                'delivery_fee_label' => $this->moneyLabel($transaction->delivery_fee),
                'discount_amount' => $transaction->discount_amount,
                'discount_amount_label' => $this->moneyLabel($transaction->discount_amount),
                'total_amount' => $transaction->total_amount,
                'total_amount_label' => $this->moneyLabel($transaction->total_amount),
                'delivery_method' => $transaction->delivery_method,
                'pickup_time_option' => $transaction->pickup_time_option,
                'pickup_scheduled_at' => $transaction->pickup_scheduled_at,
                'payment_method' => $transaction->payment_method,
                'payment_method_option_name' => $transaction->payment_method_option_name,
                'promo_code' => $transaction->promo_code,
                'promo_name' => $transaction->promo_name,
                'promo_discount_type' => $transaction->promo_discount_type,
                'promo_discount_value' => $transaction->promo_discount_value,
                'transaction_at' => $transaction->transaction_at?->toIso8601String(),
            ],
        ], Response::HTTP_CREATED);
    }

    private function resolvePaymentMethod(string $code): PaymentMethod
    {
        return PaymentMethod::query()
            ->active()
            ->with('activeOptions')
            ->where('code', strtolower(trim($code)))
            ->firstOrFail();
    }

    private function resolveDeliveryMethod(?string $code): ?DeliveryMethod
    {
        if (! $code) {
            return null;
        }

        return DeliveryMethod::query()
            ->active()
            ->where('code', strtolower(trim($code)))
            ->first();
    }

    private function resolvePaymentOption(PaymentMethod $paymentMethod, ?string $optionCode): ?PaymentMethodOption
    {
        if (! $paymentMethod->requires_option) {
            return null;
        }

        foreach ($paymentMethod->activeOptions as $option) {
            if ($option->code === strtolower(trim((string) $optionCode))) {
                return $option;
            }
        }

        return null;
    }

    private function paymentStatusTitle(PaymentMethod $paymentMethod, ?PaymentMethodOption $paymentOption): string
    {
        if ($paymentMethod->code === PaymentMethod::BANK_TRANSFER && $paymentOption) {
            return 'Menunggu pembayaran '.$paymentOption->name;
        }

        return 'Menunggu pembayaran '.$paymentMethod->name;
    }

    private function resolvePromoCode(?string $code): ?PromoCode
    {
        if (! $code) {
            return null;
        }

        return PromoCode::query()
            ->available()
            ->where('code', strtoupper(trim($code)))
            ->first();
    }

    private function resolveOrderTimeOption(?string $code): ?OrderTimeOption
    {
        if (! $code) {
            return null;
        }

        return OrderTimeOption::query()
            ->active()
            ->where('code', strtolower(trim($code)))
            ->first();
    }

    private function discountAmount(PromoCode $promo, int $subtotal): int
    {
        if ($promo->discount_type === PromoCode::DISCOUNT_TYPE_PERCENTAGE) {
            $discountAmount = (int) floor($subtotal * $promo->discount_value / 100);

            if ($promo->maximum_discount_amount !== null) {
                $discountAmount = min($discountAmount, $promo->maximum_discount_amount);
            }

            return min($discountAmount, $subtotal);
        }

        return min($promo->discount_value, $subtotal);
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    private function generateOrderNumber(): string
    {
        return now('Asia/Jakarta')->format('ymdHis').strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }
}
