<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * R2/R3/R5/R6: turns coin_transactions into a ledger that can hold Pending coins,
 * expiry dates and idempotency keys. Existing rows become 'available' with no expiry.
 *
 * Only adds columns. The `type` enum is not changed (expiry is a 'spent' row with reason 'expired').
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('coin_transactions') || Schema::hasColumn('coin_transactions', 'status')) {
            return;
        }

        Schema::table('coin_transactions', function (Blueprint $table) {
            $table->string('status', 12)->default('available')->after('amount'); // pending | available | cancelled
            $table->string('reason', 20)->nullable()->after('status');          // redeemed | expired
            $table->unsignedInteger('remaining')->default(0)->after('reason');   // unspent part of a credited lot
            $table->timestamp('available_at')->nullable()->after('remaining');   // credit date (lot became Available)
            $table->timestamp('expires_at')->nullable()->after('available_at');
            $table->string('idempotency_key', 100)->nullable()->unique()->after('expires_at');
            $table->index(['status', 'expires_at'], 'coin_transactions_status_expires_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('coin_transactions') || ! Schema::hasColumn('coin_transactions', 'status')) {
            return;
        }

        Schema::table('coin_transactions', function (Blueprint $table) {
            $table->dropIndex('coin_transactions_status_expires_idx');
            $table->dropUnique(['idempotency_key']);
        });

        Schema::table('coin_transactions', function (Blueprint $table) {
            $table->dropColumn(['status', 'reason', 'remaining', 'available_at', 'expires_at', 'idempotency_key']);
        });
    }
};
