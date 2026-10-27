<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_shipping_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('mode', 20);
            $table->unsignedInteger('flat_fee')->nullable();
            $table->unsignedInteger('fee_per_km')->nullable();
            $table->decimal('max_distance_km', 10, 3)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_shipping_settings');
    }
};
