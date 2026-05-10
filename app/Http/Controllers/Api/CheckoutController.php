<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\CartItem;
use App\Models\PaymentMethod;
use App\Models\PaymentMethodOption;
use App\Models\PromoCode;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionStatusHistory;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function __invoke(CheckoutRequest $request): JsonResponse
    {
        $user = $request->user();
        $cart = $request->cart();
        $cartItems = $request->cartItems();
        $deliveryMethod = $request->deliveryMethod();
        $paymentMethod = $request->paymentMethod();
        $paymentOption = $request->paymentOption();
        $pickupTimeOption = $request->pickupTimeOption();
        $pickupScheduledAt = $request->pickupScheduledAt();
        $promo = $request->promoCode();
        $subtotal = $request->subtotal();
        $deliveryFee = $request->deliveryFee();
        $discountAmount = $request->discountAmount();
        $grandTotal = $request->grandTotal();

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
                    ], 404));
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
        ], 201);
    }

    private function paymentStatusTitle(PaymentMethod $paymentMethod, ?PaymentMethodOption $paymentOption): string
    {
        if ($paymentMethod->code === PaymentMethod::BANK_TRANSFER && $paymentOption) {
            return 'Menunggu pembayaran '.$paymentOption->name;
        }

        return 'Menunggu pembayaran '.$paymentMethod->name;
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
