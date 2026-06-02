<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housing_areas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('subdistrict')->nullable();
            $table->string('village_code', 10)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('type', 20)->nullable();
            $table->string('role', 20)->default('buyer');
            $table->string('agent_code', 20)->nullable()->unique();
            $table->string('password')->nullable();
            $table->foreignUuid('housing_area_id')->nullable()->constrained('housing_areas')->nullOnDelete();
            $table->text('address')->nullable();
            $table->string('landmark')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('identity_document_path')->nullable();
            $table->rememberToken();
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['email', 'role']);
            $table->unique(['phone', 'role']);
        });

        Schema::create('user_session_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_devices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_token', 500)->unique();
            $table->string('platform', 20);
            $table->string('device_name', 100)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('product_categories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('image_path');
            $table->timestamps();
        });

        Schema::create('product_units', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 50)->unique();
            $table->string('slug', 50)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('order_time_options', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('requires_schedule')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('icon_key')->nullable();
            $table->boolean('requires_option')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payment_method_options', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_method_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->string('icon_key')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['payment_method_id', 'code']);
        });

        Schema::create('delivery_methods', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('fee')->default(0);
            $table->boolean('requires_order_time')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('promo_codes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('discount_type', 50);
            $table->unsignedBigInteger('discount_value');
            $table->unsignedBigInteger('minimum_order_amount')->default(0);
            $table->unsignedBigInteger('maximum_discount_amount')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->unsignedInteger('used_quantity')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cancellation_reason_categories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('allows_free_text')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellation_reason_categories');
        Schema::dropIfExists('promo_codes');
        Schema::dropIfExists('delivery_methods');
        Schema::dropIfExists('payment_method_options');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('order_time_options');
        Schema::dropIfExists('product_units');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('user_devices');
        Schema::dropIfExists('user_session_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('housing_areas');
    }
};
