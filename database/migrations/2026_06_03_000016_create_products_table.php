<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category', 50);
            $table->string('image_url')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('original_price')->nullable();
            $table->unsignedInteger('stock')->nullable();
            $table->string('unit', 50)->nullable();
            $table->foreignUuid('product_unit_id')->nullable()->constrained('product_units')->nullOnDelete();
            $table->unsignedInteger('minimum_stock')->default(1);
            $table->boolean('is_active')->default(true);
            $table->string('weight_label')->nullable();
            $table->text('description')->nullable();
            $table->string('delivery_estimate')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
