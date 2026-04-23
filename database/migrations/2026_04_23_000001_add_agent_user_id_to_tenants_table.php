<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('agent_user_id')->nullable()->after('owner_user_id')->constrained('users')->nullOnDelete();
        });

        DB::table('tenants')->update([
            'agent_user_id' => DB::raw('owner_user_id'),
        ]);
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agent_user_id');
        });
    }
};
