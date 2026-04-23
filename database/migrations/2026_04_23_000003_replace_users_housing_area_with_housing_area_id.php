<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('housing_area_id')->nullable()->after('type')->constrained('housing_areas')->nullOnDelete();
        });

        DB::table('users')->update([
            'housing_area_id' => null,
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('housing_area');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('housing_area')->nullable()->after('type');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('housing_area_id');
        });
    }
};
