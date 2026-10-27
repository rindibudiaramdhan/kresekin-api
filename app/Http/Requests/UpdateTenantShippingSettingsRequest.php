<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantShippingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in(['free', 'flat', 'distance'])],
            'flat_fee' => ['nullable', 'required_if:mode,flat', 'prohibited_unless:mode,flat', 'integer', 'min:0', 'max:1000000000'],
            'fee_per_km' => ['nullable', 'required_if:mode,distance', 'prohibited_unless:mode,distance', 'integer', 'min:0', 'max:1000000000'],
            'max_distance_km' => ['nullable', 'numeric', 'gt:0', 'max:50000', 'decimal:0,3'],
        ];
    }
}
