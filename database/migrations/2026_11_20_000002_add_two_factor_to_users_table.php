<?php

/**
 * DATABASE_RULES note:
 * All columns are nullable — existing admin rows are unaffected and 2FA remains opt-in.
 * two_factor_secret is encrypted at the application layer (AES-256-CBC via Crypt::encrypt)
 * before being stored; the column holds a ciphertext string.
 * two_factor_recovery_codes holds JSON-encoded bcrypt-hashed recovery codes — never
 * plaintext.
 * two_factor_confirmed_at is null until the admin completes the setup verification step.
 * two_factor_last_used_step stores the Google2FA timestep (Unix seconds / 30) of the
 * most recently accepted TOTP code, persisted on the user row so replay protection
 * survives across sessions. Nullable, unsigned — zero/absent means "no code used yet".
 * Rollback drops all four columns safely.
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->unsignedBigInteger('two_factor_last_used_step')->nullable()->after('two_factor_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'two_factor_last_used_step']);
        });
    }
};
