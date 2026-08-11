<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OwnerMonitoringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('date')) {
            $this->merge(['date' => now('Asia/Jakarta')->toDateString()]);
        }
    }

    public function rules(): array
    {
        return [
            'seller_id' => ['nullable', 'uuid'],
            'store_id' => ['nullable', 'uuid'],
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
