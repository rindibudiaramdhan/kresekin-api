<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignUuid('branch_owner_user_id')
                ->nullable()
                ->after('role')
                ->constrained('users')
                ->nullOnDelete();
            $table->index(['branch_owner_user_id', 'role'], 'users_branch_owner_role_index');
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->index('owner_user_id', 'tenants_owner_user_monitoring_index');
        });

        Schema::table('transaction_items', function (Blueprint $table): void {
            $table->index('tenant_id', 'transaction_items_tenant_monitoring_index');
            $table->index('transaction_id', 'transaction_items_transaction_monitoring_index');
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->index(['transaction_at', 'status'], 'transactions_monitoring_date_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex('transactions_monitoring_date_status_index');
        });

        Schema::table('transaction_items', function (Blueprint $table): void {
            $table->dropIndex('transaction_items_tenant_monitoring_index');
            $table->dropIndex('transaction_items_transaction_monitoring_index');
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropIndex('tenants_owner_user_monitoring_index');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_branch_owner_role_index');
            $table->dropConstrainedForeignId('branch_owner_user_id');
        });
    }
};
