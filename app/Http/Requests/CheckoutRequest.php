<?php

namespace App\Http\Requests;

use App\Models\Cart;
use App\Support\DeliveryMethodCatalog;
use App\Support\PaymentMethodCatalog;
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
            'payment_method_code' => ['required', 'string', Rule::in(PaymentMethodCatalog::codes())],
            'payment_method_option_code' => ['nullable', 'string', 'max:50'],
            'pickup_time_option' => ['nullable', 'string', Rule::in(['sekarang', 'jadwalkan'])],
            'pickup_scheduled_at' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $cart = Cart::query()->firstOrCreate([
                'user_id' => $this->user()->id,
            ]);
            $isPickup = $cart->delivery_method_code === DeliveryMethodCatalog::PICKUP;

            $paymentMethod = PaymentMethodCatalog::find($this->input('payment_method_code'));

            if (! $paymentMethod) {
                return;
            }

            $optionCode = $this->input('payment_method_option_code');

            if ($paymentMethod['requires_option'] && ! $optionCode) {
                $validator->errors()->add('payment_method_option_code', 'The payment method option code field is required.');

                return;
            }

            if (! $paymentMethod['requires_option']) {
                return;
            }

            $validOptionCodes = collect($paymentMethod['options'])->pluck('code')->all();

            if (! in_array($optionCode, $validOptionCodes, true)) {
                $validator->errors()->add('payment_method_option_code', 'The selected payment method option code is invalid.');
            }

            if (! $isPickup) {
                return;
            }

            $pickupTimeOption = $this->input('pickup_time_option');

            if (! $pickupTimeOption) {
                $validator->errors()->add('pickup_time_option', 'The pickup time option field is required.');

                return;
            }

            if ($pickupTimeOption === 'jadwalkan' && ! $this->input('pickup_scheduled_at')) {
                $validator->errors()->add('pickup_scheduled_at', 'The pickup scheduled at field is required.');
            }
        });
    }
}
