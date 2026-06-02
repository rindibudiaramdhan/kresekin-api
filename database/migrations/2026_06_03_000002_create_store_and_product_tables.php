<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('agent_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('profile_picture_url')->nullable();
            $table->decimal('rating', 2, 1)->default(0);
            $table->string('category', 50);
            $table->foreignUuid('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->text('location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('open_time', 5)->nullable();
            $table->string('close_time', 5)->nullable();
            $table->timestamps();
        });

        Schema::create('housing_area_tenant', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('housing_area_id')->constrained('housing_areas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'housing_area_id']);
        });

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

        Schema::create('carts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('delivery_method_code')->nullable();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('products');
        Schema::dropIfExists('housing_area_tenant');
        Schema::dropIfExists('tenants');
    }
};
