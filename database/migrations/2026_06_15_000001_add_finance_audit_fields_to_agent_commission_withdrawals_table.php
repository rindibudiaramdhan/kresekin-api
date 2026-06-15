<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_commission_withdrawals', function (Blueprint $table): void {
            $table->string('rejection_reason')->nullable()->after('note');
            $table->foreignUuid('approved_by_user_id')->nullable()->after('processed_at')->constrained('users')->nullOnDelete();
            $table->foreignUuid('rejected_by_user_id')->nullable()->after('approved_by_user_id')->constrained('users')->nullOnDelete();
            $table->foreignUuid('paid_by_user_id')->nullable()->after('rejected_by_user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('paid_by_user_id');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->timestamp('paid_at')->nullable()->after('rejected_at');
        });
    }

    public function down(): void
    {
        Schema::table('agent_commission_withdrawals', function (Blueprint $table): void {
            $table->dropForeign(['approved_by_user_id']);
            $table->dropForeign(['rejected_by_user_id']);
            $table->dropForeign(['paid_by_user_id']);
            $table->dropColumn([
                'rejection_reason',
                'approved_by_user_id',
                'rejected_by_user_id',
                'paid_by_user_id',
                'approved_at',
                'rejected_at',
                'paid_at',
            ]);
        });
    }
};
