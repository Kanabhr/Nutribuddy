<?php

/**
 * DATABASE_RULES note:
 * Append-only table. No foreign-key constraints on auditable columns (they may reference
 * deleted rows). Soft-string actor_type discriminates admin/customer/system actors. All
 * columns nullable where the value may be absent (system actions have no IP or actor).
 * Rollback drops the table with no data-loss risk — audit history is advisory, not
 * transactional.  Running on a populated DB is safe: no existing table is touched.
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('actor_type', 20)->default('system'); // admin | customer | system
            $table->string('action', 60);                        // updated | created | deleted
            $table->string('auditable_type', 120);               // model class short name
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            // No updated_at — this table is append-only.
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
