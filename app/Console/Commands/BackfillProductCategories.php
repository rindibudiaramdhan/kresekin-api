<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class BackfillProductCategories extends Command
{
    protected $signature = 'products:backfill-categories
                            {--apply : Simpan hasil backfill ke tabel penghubung}';

    protected $description = 'Audit dan isi relasi kategori untuk produk lama yang belum punya kategori';

    public function handle(): int
    {
        if (! Schema::hasTable('product_category_product')) {
            $this->error('Tabel product_category_product belum tersedia.');

            return self::FAILURE;
        }

        if ($this->option('apply')) {
            $inserted = DB::transaction(fn () => $this->backfill(apply: true));

            if ($inserted === null) {
                return self::FAILURE;
            }

            $this->info("Pasangan baru tersimpan: {$inserted}.");

            return self::SUCCESS;
        }

        if ($this->backfill(apply: false) === null) {
            return self::FAILURE;
        }

        $this->info('Audit selesai. Gunakan --apply untuk menyimpan hasil.');

        return self::SUCCESS;
    }

    private function backfill(bool $apply): ?int
    {
        $pendingProducts = DB::table('products as p')
            ->whereNotExists(function (Builder $query): void {
                $query->select('pivot.product_id')
                    ->from('product_category_product as pivot')
                    ->whereColumn('pivot.product_id', 'p.id');
            });

        $categoryNames = DB::table('product_categories')
            ->select('name')
            ->selectRaw('COUNT(*) AS match_count')
            ->groupBy('name');

        $matchedProducts = (clone $pendingProducts)
            ->leftJoinSub($categoryNames, 'category_names', 'category_names.name', '=', 'p.category');

        $unresolvedCategories = (clone $matchedProducts)
            ->whereRaw('COALESCE(category_names.match_count, 0) <> 1')
            ->select('p.category')
            ->selectRaw('COUNT(*) AS product_count, COALESCE(category_names.match_count, 0) AS match_count')
            ->groupBy('p.category', 'category_names.match_count')
            ->orderBy('p.category')
            ->get();

        $readyCount = $pendingProducts->count() - $unresolvedCategories->sum('product_count');
        $this->line("Produk siap diisi: {$readyCount}.");

        if ($unresolvedCategories->isNotEmpty()) {
            $this->table(
                ['Kategori lama', 'Jumlah produk', 'Kecocokan di master'],
                $unresolvedCategories->map(fn (object $category): array => [
                    $category->category ?? '(NULL)',
                    $category->product_count,
                    $category->match_count,
                ])->all(),
            );
            $this->error('Selesaikan kategori yang tidak ditemukan atau ambigu sebelum menjalankan backfill.');

            return null;
        }

        if (! $apply || $readyCount === 0) {
            return 0;
        }

        $pairs = $matchedProducts
            ->where('category_names.match_count', 1)
            ->join('product_categories as pc', 'pc.name', '=', 'p.category')
            ->select('p.id', 'pc.id');

        $inserted = DB::table('product_category_product')
            ->insertOrIgnoreUsing(['product_id', 'product_category_id'], $pairs);

        if ($pendingProducts->exists()) {
            throw new RuntimeException('Backfill dibatalkan karena masih ada produk tanpa relasi kategori.');
        }

        return $inserted;
    }
}
