<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('type', 20)->nullable();
            $table->string('role', 20)->default('buyer');
            $table->string('agent_code', 20)->nullable()->unique();
            $table->string('password')->nullable();
            $table->foreignUuid('housing_area_id')->nullable()->constrained('housing_areas')->nullOnDelete();
            $table->text('address')->nullable();
            $table->string('landmark')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('identity_document_path')->nullable();
            $table->rememberToken();
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['email', 'role']);
            $table->unique(['phone', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
