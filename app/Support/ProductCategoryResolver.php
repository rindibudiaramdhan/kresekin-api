<?php

namespace App\Support;

use App\Models\ProductCategory;
use Illuminate\Support\Collection;

class ProductCategoryResolver
{
    public function resolve(string $name, ?Collection $categories = null): ?ProductCategory
    {
        $matches = $this->candidates($name, $categories);

        return $matches->count() === 1 ? $matches->first() : null;
    }

    public function candidates(string $name, ?Collection $categories = null): Collection
    {
        $categories ??= ProductCategory::query()->get();
        $exact = $categories->filter(fn (ProductCategory $category) => $category->name === $name);

        if ($exact->isNotEmpty()) {
            return $exact;
        }

        $normalized = $categories->filter(fn (ProductCategory $category) => $this->normalize($category->name) === $this->normalize($name));

        return $normalized;
    }

    public function normalize(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name)));
    }
}
