<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transaction_disbursements');
    }
};
