<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('category', 50)->nullable()->change();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->foreignUuid('product_category_id')->nullable()->index()
                ->constrained('product_categories')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Old writers require category. Do not invent values for new tenants on rollback.
        if (DB::table('tenants')->whereNull('category')->exists()) {
            throw new RuntimeException('Cannot roll back category expansion while tenants.category contains null values.');
        }

        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('category', 50)->nullable(false)->change();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropForeign(['product_category_id']);
            $table->dropIndex(['product_category_id']);
            $table->dropColumn('product_category_id');
        });
    }
};
