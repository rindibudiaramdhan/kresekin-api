<?php

namespace App\Http\Requests;

use App\Support\DeliveryMethodCatalog;
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
            'delivery_method_code' => ['required', 'string', Rule::in(DeliveryMethodCatalog::codes())],
        ];
    }
}
