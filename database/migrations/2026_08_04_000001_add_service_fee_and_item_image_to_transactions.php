<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->unsignedBigInteger('service_fee')->default(0)->after('delivery_fee');
        });

        Schema::table('transaction_items', function (Blueprint $table): void {
            $table->string('image_url')->nullable()->after('product_name');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table): void {
            $table->dropColumn('image_url');
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropColumn('service_fee');
        });
    }
};
