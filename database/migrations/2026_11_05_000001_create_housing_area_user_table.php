<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housing_area_user', function (Blueprint $table): void {
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('housing_area_id')->constrained('housing_areas')->cascadeOnDelete();
            $table->primary(['user_id', 'housing_area_id']);
        });

        DB::table('housing_area_user')->insertUsing(
            ['user_id', 'housing_area_id'],
            DB::table('users')->select('id', 'housing_area_id')->whereNotNull('housing_area_id'),
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('housing_area_user');
    }
};
