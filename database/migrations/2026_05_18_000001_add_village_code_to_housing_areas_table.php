<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('housing_areas', function (Blueprint $table) {
            $table->string('village_code', 10)->nullable()->after('subdistrict')->index();
        });
    }

    public function down(): void
    {
        Schema::table('housing_areas', function (Blueprint $table) {
            $table->dropIndex(['village_code']);
            $table->dropColumn('village_code');
        });
    }
};
