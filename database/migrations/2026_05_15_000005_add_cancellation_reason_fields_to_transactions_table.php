<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignUuid('cancellation_reason_category_id')
                ->nullable()
                ->after('discount_amount')
                ->constrained('cancellation_reason_categories')
                ->nullOnDelete();
            $table->text('cancellation_reason_text')->nullable()->after('cancellation_reason_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancellation_reason_category_id');
            $table->dropColumn('cancellation_reason_text');
        });
    }
};
