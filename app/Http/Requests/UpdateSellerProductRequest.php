<?php

namespace App\Http\Requests;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSellerProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => [
                'required',
                'integer',
                Rule::exists('tenants', 'id')->where(fn ($query) => $query->where('owner_user_id', $this->user()->id)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(Tenant::CATEGORIES)],
            'image_url' => ['nullable', 'url', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'original_price' => ['nullable', 'integer', 'min:0'],
            'weight_label' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'delivery_estimate' => ['nullable', 'string', 'max:100'],
        ];
    }
}
