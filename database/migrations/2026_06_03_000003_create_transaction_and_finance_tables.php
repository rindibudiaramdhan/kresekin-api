<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->string('status', 100);
            $table->unsignedBigInteger('subtotal_amount')->default(0);
            $table->unsignedBigInteger('delivery_fee')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->string('delivery_method')->nullable();
            $table->string('delivery_method_code')->nullable();
            $table->string('pickup_time_option')->nullable();
            $table->string('pickup_scheduled_at', 5)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_method_code')->nullable();
            $table->string('payment_method_option_code')->nullable();
            $table->string('payment_method_option_name')->nullable();
            $table->foreignUuid('promo_code_id')->nullable()->constrained()->nullOnDelete();
            $table->string('promo_code')->nullable();
            $table->string('promo_name')->nullable();
            $table->string('promo_discount_type', 50)->nullable();
            $table->unsignedBigInteger('promo_discount_value')->nullable();
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->foreignUuid('cancellation_reason_category_id')->nullable()->constrained('cancellation_reason_categories')->nullOnDelete();
            $table->text('cancellation_reason_text')->nullable();
            $table->timestamp('transaction_at');
            $table->timestamps();
        });

        Schema::create('transaction_status_histories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->constrained()->cascadeOnDelete();
            $table->string('status', 100);
            $table->string('title');
            $table->string('description')->nullable();
            $table->unsignedInteger('sequence')->default(1);
            $table->timestamp('status_at')->nullable();
            $table->timestamps();
        });

        Schema::create('transaction_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('line_total');
            $table->timestamps();
        });

        Schema::create('agent_commission_withdrawals', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('agent_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('status')->default('requested');
            $table->text('note')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['agent_user_id', 'status']);
        });

        Schema::create('finance_transaction_disbursements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('seller_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('unique_code')->unique();
            $table->unsignedBigInteger('amount');
            $table->string('status')->default('pending_buyer_payment');
            $table->timestamp('buyer_payment_confirmed_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->foreignUuid('confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('disbursed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['transaction_id', 'tenant_id']);
            $table->index(['status', 'created_at']);
        });

        DB::table('cancellation_reason_categories')->insert([
            [
                'id' => (string) Str::uuid(),
                'name' => 'Salah Pesan / Salah Produk',
                'sort_order' => 10,
                'allows_free_text' => false,
                'is_active' => true,
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Ingin Ubah Alamat',
                'sort_order' => 20,
                'allows_free_text' => false,
                'is_active' => true,
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Pengiriman Terlalu Lama',
                'sort_order' => 30,
                'allows_free_text' => false,
                'is_active' => true,
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
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
        Schema::dropIfExists('finance_transaction_disbursements');
        Schema::dropIfExists('agent_commission_withdrawals');
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transaction_status_histories');
        Schema::dropIfExists('transactions');
    }
};
