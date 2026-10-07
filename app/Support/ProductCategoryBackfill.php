<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductCategoryBackfill
{
    public function inventory(): array
    {
        $products = DB::table('products');

        return [
            'total' => (clone $products)->count(),
            'soft_deleted' => (clone $products)->whereNotNull('deleted_at')->count(),
            'null' => (clone $products)->whereNull('product_category_id')->count(),
            'orphan' => (clone $products)->whereNotNull('product_category_id')->whereNotExists(function ($query) {
                $query->selectRaw('1')->from('product_categories')->whereColumn('product_categories.id', 'products.product_category_id');
            })->count(),
            'checked_at' => now()->toIso8601String(),
        ];
    }

    public function plan(Product $product, Collection $categories, array $mapping): array
    {
        $row = [
            'product_id' => $product->id,
            'legacy_category' => $product->category,
            'current_id' => $product->product_category_id,
            'soft_deleted' => $product->trashed(),
            'target_id' => null,
        ];
        if ($product->product_category_id !== null) {
            $row['status'] = $categories->contains('id', $product->product_category_id) ? 'valid_id' : 'invalid_id';

            return $row;
        }

        if (array_key_exists($product->category, $mapping)) {
            $row['target_id'] = $mapping[$product->category];
            $row['status'] = 'mapped';

            return $row;
        }

        $matches = app(ProductCategoryResolver::class)->candidates($product->category, $categories);
        $row['status'] = match ($matches->count()) {
            0 => 'unmatched',
            1 => 'matched',
            default => 'ambiguous',
        };
        $row['target_id'] = $matches->count() === 1 ? $matches->first()->id : null;
        $row['candidate_ids'] = $matches->pluck('id')->values()->all();

        return $row;
    }

    public function apply(Product $snapshot, string $targetId, string $targetName): bool
    {
        // Conditional update includes soft-deleted products and protects concurrent writes.
        return DB::table('products')
            ->where('id', $snapshot->id)
            ->whereNull('product_category_id')
            ->where('category', $snapshot->category)
            ->update(['product_category_id' => $targetId, 'category' => $targetName, 'updated_at' => now()]) === 1;
    }
}
