<?php

namespace App\Http\Requests;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSellerProductRequest extends FormRequest
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
                'uuid',
                Rule::exists('tenants', 'id')->where(fn ($query) => $query->where('owner_user_id', $this->user()->id)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(Tenant::CATEGORIES)],
            'image' => ['required_without_all:image_url,image_path', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'image_path' => ['required_without_all:image,image_url', 'nullable', 'string', 'max:255', 'starts_with:products/'],
            'image_url' => ['required_without_all:image,image_path', 'nullable', 'url', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'original_price' => ['nullable', 'integer', 'min:0', 'gte:price'],
            'stock' => ['required', 'integer', 'min:0', 'max:999999'],
            'unit' => ['required', 'string', 'max:50'],
            'minimum_stock' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['nullable', 'boolean'],
            'weight_label' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'delivery_estimate' => ['nullable', 'string', 'max:100'],
        ];
    }
}
