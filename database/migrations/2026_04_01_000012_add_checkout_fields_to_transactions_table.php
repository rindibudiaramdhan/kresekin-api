<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('subtotal_amount')->default(0)->after('status');
            $table->unsignedBigInteger('delivery_fee')->default(0)->after('subtotal_amount');
            $table->string('delivery_method_code')->nullable()->after('delivery_method');
            $table->string('payment_method_code')->nullable()->after('payment_method');
            $table->string('payment_method_option_code')->nullable()->after('payment_method_code');
            $table->string('payment_method_option_name')->nullable()->after('payment_method_option_code');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal_amount',
                'delivery_fee',
                'delivery_method_code',
                'payment_method_code',
                'payment_method_option_code',
                'payment_method_option_name',
            ]);
        });
    }
};
