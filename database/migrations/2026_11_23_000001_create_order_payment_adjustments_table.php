<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue 33: manual payment adjustments (mark paid, refund, partial refund) kept apart from gateway transactions.
 *
 * Reason: payment status changes made by an admin need a permission, a reason, a reference and a permanent record.
 * Table: order_payment_adjustments (new, append-only ledger). The existing payments table is not changed.
 * Existing data: untouched. Nothing is back-filled.
 * Rollback: down() drops the table, so every manual adjustment recorded after deploy is lost.
 *   The order's payment_status is NOT reverted by a rollback.
 * Production: the owner runs `php artisan migrate` manually. Never from AI or CI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_payment_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // mark_paid | refund | partial_refund
            $table->decimal('amount', 12, 2);
            $table->string('from_payment_status', 30);
            $table->string('to_payment_status', 30);
            $table->text('reason');
            $table->string('reference', 100);
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('adjusted_by_name', 150); // snapshot, so the name survives if the admin is deleted
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payment_adjustments');
    }
};