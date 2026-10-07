<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('category', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('tenants')->whereNull('category')->update(['category' => '']);

        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('category', 50)->nullable(false)->change();
        });
    }
};
