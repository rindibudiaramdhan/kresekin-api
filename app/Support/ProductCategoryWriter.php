<?php

namespace App\Support;

use App\Models\ProductCategory;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductCategoryWriter
{
    public function attributes(array $data): array
    {
        $id = $data['product_category_id'] ?? null;
        $category = is_string($id) && Str::isUuid($id) ? ProductCategory::query()->find($id) : null;
        if (! $category) {
            throw ValidationException::withMessages(['product_category_id' => 'Pilih kategori master yang valid.']);
        }

        $data['product_category_id'] = $category->id;
        $data['category'] = $category->name;

        return $data;
    }
}
