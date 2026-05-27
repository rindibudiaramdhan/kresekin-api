<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_units', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 50)->unique();
            $table->string('slug', 50)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->foreignUuid('product_unit_id')
                ->nullable()
                ->after('unit')
                ->constrained('product_units')
                ->nullOnDelete();
        });

        $now = now();
        $units = collect([
            'buah',
            'ikat',
            'kilogram',
            'gram',
            'liter',
            'pcs',
            'pak',
            'pack',
            'botol',
            'kaleng',
            'dus',
            'sachet',
            'porsi',
            'meter',
            'roll',
        ])->map(fn (string $unit): array => [
            'id' => (string) str()->uuid(),
            'name' => $unit,
            'slug' => str($unit)->slug()->toString(),
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::table('product_units')->insert($units);

        foreach ($units as $unit) {
            DB::table('products')
                ->where('unit', $unit['name'])
                ->update(['product_unit_id' => $unit['id']]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('product_unit_id');
        });

        Schema::dropIfExists('product_units');
    }
};
