<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('pickup_time_option')->nullable()->after('delivery_method_code');
            $table->string('pickup_scheduled_at', 5)->nullable()->after('pickup_time_option');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['pickup_time_option', 'pickup_scheduled_at']);
        });
    }
};
