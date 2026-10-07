<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\ProductCategoryBackfill;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class BackfillProductCategories extends Command implements Isolatable
{
    protected $signature = 'products:backfill-categories
        {--run= : UUID for durable reports/checkpoint; reuse to resume}
        {--apply : Write candidates; otherwise inventory/dry-run only}
        {--disk=s3 : Durable operational storage disk}
        {--mapping= : Mapping JSON path on the same disk}
        {--batch=100 : Batch size; tune during PostgreSQL rehearsal}
        {--max-batches= : Stop cleanly after this many batches}';

    protected $description = 'Audit or backfill master category IDs, including soft-deleted products';

    public function handle(ProductCategoryBackfill $backfill): int
    {
        $run = $this->option('run');
        $diskName = $this->option('disk');
        $batch = filter_var($this->option('batch'), FILTER_VALIDATE_INT);
        $maxBatches = $this->option('max-batches');
        if (! is_string($run) || ! Str::isUuid($run) || $batch === false || $batch < 1 || $batch > 10000 || ($maxBatches !== null && (filter_var($maxBatches, FILTER_VALIDATE_INT) === false || (int) $maxBatches < 1))) {
            $this->error('Run UUID, batch 1–10000 dan max-batches positif diperlukan.');

            return self::FAILURE;
        }
        if (! app()->environment('testing') && config("filesystems.disks.$diskName.driver") !== 's3') {
            $this->error('Gunakan disk S3 operasional durable; disk lokal ephemeral tidak diizinkan.');

            return self::FAILURE;
        }

        if (! app()->environment('testing') && ! $this->option('isolated')) {
            $this->error('Gunakan --isolated=1 dengan cache lock bersama untuk mencegah runner paralel.');

            return self::FAILURE;
        }

        $disk = Storage::disk($diskName);
        $prefix = 'product-category-backfill/'.$run;
        $checkpointPath = $prefix.'/checkpoint.json';
        $mapping = [];
        if ($path = $this->option('mapping')) {
            $mapping = json_decode($disk->get($path), true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($mapping) || array_is_list($mapping) && $mapping !== []) {
                throw new RuntimeException('Mapping harus objek nama kategori lama ke UUID master.');
            }
        }
        $categories = ProductCategory::query()->orderBy('id')->get(['id', 'name', 'slug']);
        foreach ($mapping as $id) {
            if (! is_string($id) || ! $categories->contains('id', $id)) {
                throw new RuntimeException('Mapping mereferensikan ID master yang tidak valid.');
            }
        }
        ksort($mapping);
        $contract = [
            'apply' => (bool) $this->option('apply'),
            'mapping' => $mapping,
            'master' => $categories->toArray(),
            'environment' => app()->environment(),
            'connection' => config('database.default'),
            'database' => config('database.connections.'.config('database.default').'.database'),
            'host' => config('database.connections.'.config('database.default').'.host'),
            'url' => config('database.connections.'.config('database.default').'.url'),
        ];
        // Avoid storing database configuration or credentials in operational artifacts.
        $fingerprint = hash('sha256', json_encode($contract, JSON_THROW_ON_ERROR));
        if ($disk->exists($checkpointPath)) {
            $state = json_decode($disk->get($checkpointPath), true, flags: JSON_THROW_ON_ERROR);
            if ($state['fingerprint'] !== $fingerprint) {
                throw new RuntimeException('Mode/mapping/master/database berubah. Buat run UUID baru dan review ulang.');
            }
        } else {
            $state = [
                'run' => $run,
                'fingerprint' => $fingerprint,
                'apply' => $contract['apply'],
                'mapping' => $mapping,
                'master' => $categories->toArray(),
                'upper_id' => Product::withTrashed()->orderByDesc('id')->value('id'),
                'cursor' => null,
                'batches' => 0,
                'counts' => [],
                'completed' => false,
                'before' => $backfill->inventory(),
            ];
            // Validate durable write access before touching any product data.
            $this->persist($disk, $checkpointPath, $state);
        }

        $processedBatches = 0;
        while (! $state['completed']) {
            $query = Product::withTrashed()->select(['id', 'category', 'product_category_id', 'deleted_at'])->orderBy('id');
            if ($state['cursor'] !== null) {
                $query->where('id', '>', $state['cursor']);
            }
            $products = $state['upper_id'] === null ? collect() : $query->where('id', '<=', $state['upper_id'])->limit($batch)->get();
            if ($products->isEmpty()) {
                $state['completed'] = true;
                break;
            }
            $attempt = (string) Str::uuid();
            $plans = $products->map(fn (Product $product) => $backfill->plan($product, $categories, $mapping));
            $this->persist($disk, $prefix.'/plans/'.$attempt.'.json', ['rows' => $plans->values()->all(), 'recorded_at' => now()->toIso8601String()]);
            $rows = [];
            foreach ($products as $index => $product) {
                $row = $plans[$index];
                if ($contract['apply'] && $row['target_id'] !== null) {
                    $target = $categories->firstWhere('id', $row['target_id']);
                    $row['status'] = $backfill->apply($product, $target->id, $target->name) ? 'updated' : 'conflict';
                }
                $state['counts'][$row['status']] = ($state['counts'][$row['status']] ?? 0) + 1;
                $rows[] = $row;
            }
            $state['batches']++;
            // Immutable attempt reports survive a crash between DB writes and checkpoint.
            $reportPath = $prefix.'/batches/'.$state['batches'].'-'.Str::uuid().'.json';
            $this->persist($disk, $reportPath, ['rows' => $rows, 'recorded_at' => now()->toIso8601String()]);
            $state['cursor'] = $products->last()->id;
            $state['after'] = $backfill->inventory();
            $this->persist($disk, $checkpointPath, $state);
            $this->line('Batch '.$state['batches'].' tersimpan; '.count($rows).' produk.');
            $processedBatches++;
            if ($maxBatches !== null && $processedBatches >= (int) $maxBatches) {
                break;
            }
        }
        $state['after'] = $backfill->inventory();
        $this->persist($disk, $checkpointPath, $state);
        $this->info(json_encode(['completed' => $state['completed'], 'counts' => $state['counts'], 'reconciliation' => $state['after']], JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }

    private function persist($disk, string $path, array $data): void
    {
        if (! $disk->put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))) {
            throw new RuntimeException('Gagal menyimpan laporan/checkpoint durable; proses dihentikan.');
        }
    }
}
