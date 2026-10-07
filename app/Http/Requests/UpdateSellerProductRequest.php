<?php

namespace App\Http\Requests;

use App\Models\ProductCategory;
use App\Support\ProductCategoryResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
                'uuid',
                Rule::exists('tenants', 'id')->where(fn ($query) => $query->where('owner_user_id', $this->user()->id)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'product_category_id' => [$this->exists('product_category_id') ? 'required' : 'sometimes', 'uuid', Rule::exists('product_categories', 'id')],
            'category' => $this->exists('product_category_id') ? ['exclude'] : ['required', 'string', 'max:255'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'image_path' => ['nullable', 'string', 'max:255', 'starts_with:products/'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'original_price' => ['nullable', 'integer', 'min:0', 'gte:price'],
            'stock' => ['required', 'integer', 'min:0', 'max:999999'],
            'product_unit_id' => [
                'required',
                'uuid',
                Rule::exists('product_units', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'minimum_stock' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['nullable', 'boolean'],
            'weight_label' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'delivery_estimate' => ['nullable', 'string', 'max:100'],
        ];
    }

    private ?ProductCategory $resolvedCategory = null;

    public function productCategory(): ProductCategory
    {
        return $this->resolvedCategory ?? ProductCategory::query()->findOrFail($this->validated('product_category_id'));
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->exists('product_category_id')) {
                return;
            }

            if (! $validator->errors()->has('category')) {
                $this->resolvedCategory = app(ProductCategoryResolver::class)->resolve($this->input('category'));
                if (! $this->resolvedCategory) {
                    $validator->errors()->add('category', 'Kategori tidak cocok dengan satu kategori master yang unik.');
                }
            }

            Log::info('product_category_legacy_fallback', [
                'outcome' => $validator->errors()->isEmpty() ? 'accepted' : 'rejected',
                'route' => $this->route()?->uri(),
                'method' => $this->method(),
                'environment' => app()->environment(),
                'request_id' => $this->attributes->get('request_id'),
            ]);
        }];
    }
}
