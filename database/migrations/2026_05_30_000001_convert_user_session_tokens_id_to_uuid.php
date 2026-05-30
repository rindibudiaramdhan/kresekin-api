<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasTable('user_session_tokens')) {
            return;
        }

        $columnType = Schema::getColumnType('user_session_tokens', 'id');

        if (! in_array($columnType, ['bigint', 'integer'], true)) {
            return;
        }

        DB::statement('ALTER TABLE user_session_tokens DROP CONSTRAINT IF EXISTS user_session_tokens_pkey');
        DB::statement('ALTER TABLE user_session_tokens ALTER COLUMN id DROP DEFAULT');
        DB::statement("ALTER TABLE user_session_tokens ALTER COLUMN id TYPE uuid USING md5(id::text)::uuid");
        DB::statement('ALTER TABLE user_session_tokens ADD PRIMARY KEY (id)');
    }

    public function down(): void
    {
        // Intentionally left empty. Converting UUID primary keys back to bigint would
        // require generating new IDs and can break references held outside this table.
    }
};
