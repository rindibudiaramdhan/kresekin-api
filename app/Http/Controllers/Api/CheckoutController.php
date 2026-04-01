<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionStatusHistory;
use App\Support\DeliveryMethodCatalog;
use App\Support\PaymentMethodCatalog;
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

        $deliveryMethod = DeliveryMethodCatalog::find($cart->delivery_method_code);

        if (! $deliveryMethod) {
            return response()->json([
                'message' => 'Metode pengiriman belum dipilih di keranjang.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paymentMethod = PaymentMethodCatalog::find($validated['payment_method_code']);
        $paymentOption = $this->resolvePaymentOption($paymentMethod, $validated['payment_method_option_code'] ?? null);
        $pickupTimeOption = $deliveryMethod['code'] === DeliveryMethodCatalog::PICKUP
            ? ($validated['pickup_time_option'] ?? null)
            : null;
        $pickupScheduledAt = $pickupTimeOption === 'jadwalkan'
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
        $deliveryFee = $deliveryMethod['fee'];
        $grandTotal = $subtotal + $deliveryFee;

        $transaction = DB::transaction(function () use (
            $user,
            $cart,
            $cartItems,
            $deliveryMethod,
            $paymentMethod,
            $paymentOption,
            $pickupTimeOption,
            $pickupScheduledAt,
            $subtotal,
            $deliveryFee,
            $grandTotal
        ): Transaction {
            $transaction = Transaction::query()->create([
                'user_id' => $user->id,
                'order_number' => $this->generateOrderNumber(),
                'status' => Transaction::STATUS_PENDING_PAYMENT,
                'subtotal_amount' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $grandTotal,
                'delivery_method' => $deliveryMethod['name'],
                'delivery_method_code' => $deliveryMethod['code'],
                'pickup_time_option' => $pickupTimeOption,
                'pickup_scheduled_at' => $pickupScheduledAt,
                'payment_method' => $paymentMethod['name'],
                'payment_method_code' => $paymentMethod['code'],
                'payment_method_option_code' => $paymentOption['code'] ?? null,
                'payment_method_option_name' => $paymentOption['name'] ?? null,
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
                'total_amount' => $transaction->total_amount,
                'total_amount_label' => $this->moneyLabel($transaction->total_amount),
                'delivery_method' => $transaction->delivery_method,
                'pickup_time_option' => $transaction->pickup_time_option,
                'pickup_scheduled_at' => $transaction->pickup_scheduled_at,
                'payment_method' => $transaction->payment_method,
                'payment_method_option_name' => $transaction->payment_method_option_name,
                'transaction_at' => $transaction->transaction_at?->toIso8601String(),
            ],
        ], Response::HTTP_CREATED);
    }

    private function resolvePaymentOption(array $paymentMethod, ?string $optionCode): ?array
    {
        if (! $paymentMethod['requires_option']) {
            return null;
        }

        foreach ($paymentMethod['options'] as $option) {
            if ($option['code'] === $optionCode) {
                return $option;
            }
        }

        return null;
    }

    private function paymentStatusTitle(array $paymentMethod, ?array $paymentOption): string
    {
        if ($paymentMethod['code'] === PaymentMethodCatalog::BANK_TRANSFER && $paymentOption) {
            return 'Menunggu pembayaran '.$paymentOption['name'];
        }

        return 'Menunggu pembayaran '.$paymentMethod['name'];
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
