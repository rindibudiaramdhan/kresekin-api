<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('total_amount')->default(0)->after('status');
            $table->string('delivery_method')->nullable()->after('total_amount');
            $table->string('payment_method', 50)->nullable()->after('delivery_method');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['total_amount', 'delivery_method', 'payment_method']);
        });
    }
};
