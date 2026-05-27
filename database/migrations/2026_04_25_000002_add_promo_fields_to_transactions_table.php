<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignUuid('promo_code_id')->nullable()->after('payment_method_option_name')->constrained()->nullOnDelete();
            $table->string('promo_code')->nullable()->after('promo_code_id');
            $table->string('promo_name')->nullable()->after('promo_code');
            $table->string('promo_discount_type', 50)->nullable()->after('promo_name');
            $table->unsignedBigInteger('promo_discount_value')->nullable()->after('promo_discount_type');
            $table->unsignedBigInteger('discount_amount')->default(0)->after('promo_discount_value');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promo_code_id');
            $table->dropColumn([
                'promo_code',
                'promo_name',
                'promo_discount_type',
                'promo_discount_value',
                'discount_amount',
            ]);
        });
    }
};
