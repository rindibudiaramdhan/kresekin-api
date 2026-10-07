<?php

namespace App\Support;

use App\Models\Product;

class ProductCategoryPresenter
{
    public static function fields(Product $product, bool $withMetadata = false): array
    {
        $category = $product->productCategory;
        $fields = [
            'product_category_id' => $product->product_category_id,
            'product_category' => $category ? [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ] : null,
            'category' => $product->categoryName(),
        ];
        if ($withMetadata) {
            $slug = $product->categorySlug();
            $metadata = ProductCategoryMetadata::forSlug($slug);
            $fields += [
                'category_slug' => $slug,
                'category_icon_key' => $metadata['icon_key'],
                'category_background_color' => $metadata['background_color'],
                'category_icon_color' => $metadata['icon_color'],
            ];
        }

        return $fields;
    }
}
