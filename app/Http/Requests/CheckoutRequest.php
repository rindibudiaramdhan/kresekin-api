<?php

namespace App\Http\Requests;

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
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
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
        });
    }
}
