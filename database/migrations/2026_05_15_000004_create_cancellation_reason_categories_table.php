<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancellation_reason_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('allows_free_text')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        DB::table('cancellation_reason_categories')->insert([
            [
                'name' => 'Salah Pesan / Salah Produk',
                'sort_order' => 10,
                'allows_free_text' => false,
                'is_active' => true,
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ingin Ubah Alamat',
                'sort_order' => 20,
                'allows_free_text' => false,
                'is_active' => true,
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pengiriman Terlalu Lama',
                'sort_order' => 30,
                'allows_free_text' => false,
                'is_active' => true,
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Alasan Lainnya',
                'sort_order' => 999,
                'allows_free_text' => true,
                'is_active' => true,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellation_reason_categories');
    }
};
