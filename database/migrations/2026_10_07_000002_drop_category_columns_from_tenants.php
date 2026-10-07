<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('product_category_id');
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        // Restore the schema only; removed category values cannot be recovered.
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('category', 50)->nullable();
            $table->foreignUuid('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
        });
    }
};
