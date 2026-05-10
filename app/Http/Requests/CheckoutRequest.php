<?php

namespace App\Http\Requests;

use App\Models\Cart;
use App\Models\OrderTimeOption;
use App\Models\PaymentMethod;
use App\Support\DeliveryMethodCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
            $cart = Cart::query()->firstOrCreate([
                'user_id' => $this->user()->id,
            ]);
            $isPickup = $cart->delivery_method_code === DeliveryMethodCatalog::PICKUP;

            $paymentMethod = PaymentMethod::query()
                ->active()
                ->with('activeOptions')
                ->where('code', strtolower(trim((string) $this->input('payment_method_code'))))
                ->first();

            if (! $paymentMethod) {
                return;
            }

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

            if (! $isPickup) {
                return;
            }

            $pickupTimeOption = $this->input('pickup_time_option');

            if (! $pickupTimeOption) {
                $validator->errors()->add('pickup_time_option', 'Kolom opsi waktu pengambilan wajib diisi.');

                return;
            }

            $orderTimeOption = OrderTimeOption::query()
                ->active()
                ->where('code', strtolower(trim($pickupTimeOption)))
                ->first();

            if ($orderTimeOption?->requires_schedule && ! $this->input('pickup_scheduled_at')) {
                $validator->errors()->add('pickup_scheduled_at', 'Kolom jadwal waktu pengambilan wajib diisi.');
            }
        });
    }
}
