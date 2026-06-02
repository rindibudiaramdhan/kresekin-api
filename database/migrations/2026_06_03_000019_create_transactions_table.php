<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
