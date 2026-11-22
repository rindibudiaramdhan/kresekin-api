<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_category_product', function (Blueprint $table): void {
            $table->foreignUuid('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignUuid('product_category_id')
                ->constrained('product_categories')
                ->restrictOnDelete();

            $table->primary(['product_id', 'product_category_id']);
            $table->index(['product_category_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_category_product');
    }
};
