<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('image_path')->nullable()->after('image_url');
            $table->unsignedInteger('stock')->nullable()->after('original_price');
            $table->string('unit', 50)->nullable()->after('stock');
            $table->unsignedInteger('minimum_stock')->default(1)->after('unit');
            $table->boolean('is_active')->default(true)->after('minimum_stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'image_path',
                'stock',
                'unit',
                'minimum_stock',
                'is_active',
            ]);
        });
    }
};
