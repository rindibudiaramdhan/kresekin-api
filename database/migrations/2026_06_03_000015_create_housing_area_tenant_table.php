<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housing_area_tenant', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('housing_area_id')->constrained('housing_areas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'housing_area_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housing_area_tenant');
    }
};
