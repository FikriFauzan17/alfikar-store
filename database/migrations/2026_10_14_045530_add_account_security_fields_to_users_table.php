<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->text('totp_secret')->nullable();
            $table->longText('totp_recovery_codes')->nullable();
            $table->timestamp('totp_enabled_at')->nullable();
            $table->char('totp_last_code_fingerprint', 64)->nullable();
            $table->timestamp('totp_last_code_used_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'totp_secret',
                'totp_recovery_codes',
                'totp_enabled_at',
                'totp_last_code_fingerprint',
                'totp_last_code_used_at',
            ]);
        });
    }
};
