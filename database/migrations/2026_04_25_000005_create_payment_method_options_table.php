<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_method_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_method_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->string('icon_key')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['payment_method_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_method_options');
    }
};
