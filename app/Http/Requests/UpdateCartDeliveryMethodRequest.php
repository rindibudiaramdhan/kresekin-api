<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCartDeliveryMethodRequest extends FormRequest
{
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
        ];
    }
}
