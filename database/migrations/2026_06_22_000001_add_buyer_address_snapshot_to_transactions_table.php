<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->text('buyer_address')->nullable();
            $table->string('buyer_landmark')->nullable();
            $table->decimal('buyer_latitude', 10, 7)->nullable();
            $table->decimal('buyer_longitude', 10, 7)->nullable();
            $table->timestamp('buyer_address_snapshot_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropColumn([
                'buyer_address',
                'buyer_landmark',
                'buyer_latitude',
                'buyer_longitude',
                'buyer_address_snapshot_at',
            ]);
        });
    }
};
