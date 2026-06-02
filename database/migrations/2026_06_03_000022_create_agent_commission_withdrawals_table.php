<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_commission_withdrawals');
    }
};
