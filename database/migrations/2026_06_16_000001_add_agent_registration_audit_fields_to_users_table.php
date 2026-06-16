<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('terms_accepted_at')->nullable()->after('identity_document_path');
            $table->string('terms_version')->nullable()->after('terms_accepted_at');
            $table->timestamp('privacy_accepted_at')->nullable()->after('terms_version');
            $table->string('agent_verification_status')->nullable()->after('privacy_accepted_at');
            $table->timestamp('agent_verified_at')->nullable()->after('agent_verification_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'terms_accepted_at',
                'terms_version',
                'privacy_accepted_at',
                'agent_verification_status',
                'agent_verified_at',
            ]);
        });
    }
};
