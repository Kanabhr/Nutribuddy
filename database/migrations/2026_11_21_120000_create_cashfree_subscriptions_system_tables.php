<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Missing columns in subscription_plans (Section 4 of PDF spec)
        Schema::table('subscription_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('subscription_plans', 'cashfree_plan_id')) {
                $table->string('cashfree_plan_id', 100)->nullable()->after('level');
            }
            if (! Schema::hasColumn('subscription_plans', 'interval_type')) {
                $table->string('interval_type', 10)->nullable()->after('cashfree_plan_id'); // MONTH | YEAR
            }
            if (! Schema::hasColumn('subscription_plans', 'intervals')) {
                $table->unsignedInteger('intervals')->nullable()->after('interval_type');
            }
            if (! Schema::hasColumn('subscription_plans', 'max_cycles')) {
                $table->unsignedInteger('max_cycles')->nullable()->after('intervals');
            }
        });

        // 2. Subscriptions table (Section 4 of PDF spec)
        if (! Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
                $table->foreignId('child_id')->nullable()->constrained('children')->nullOnDelete();
                $table->string('cashfree_subscription_id', 100)->unique();
                $table->string('cf_subscription_id', 100)->nullable();
                $table->string('status', 30)->default('INITIALIZED'); // INITIALIZED, ACTIVE, PAST_DUE, CANCELLED, etc.
                $table->timestamp('current_period_start')->nullable();
                $table->timestamp('current_period_end')->nullable();
                $table->timestamp('grace_until')->nullable();
                $table->boolean('cancel_at_period_end')->default(false);
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }

        // 3. Extend payments table for subscription charges
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->change();
            if (! Schema::hasColumn('payments', 'subscription_id')) {
                $table->foreignId('subscription_id')->nullable()->after('order_id')->constrained('subscriptions')->nullOnDelete();
            }
            if (! Schema::hasColumn('payments', 'payment_type')) {
                $table->string('payment_type', 20)->nullable()->after('transaction_type'); // AUTH | CHARGE
            }
            if (! Schema::hasColumn('payments', 'failure_reason')) {
                $table->text('failure_reason')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('payments', 'invoice_number')) {
                $table->string('invoice_number', 50)->nullable()->after('gateway_payment_id');
            }
        });

        // 4. Webhook events table for idempotency (Section 4 of PDF spec)
        if (! Schema::hasTable('webhook_events')) {
            Schema::create('webhook_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_key', 200)->unique();
                $table->string('event_type', 80)->nullable();
                $table->json('payload')->nullable();
                $table->boolean('processed')->default(false);
                $table->timestamp('received_at')->useCurrent();
                $table->timestamps();
            });
        }

        // 5. Usage counters table (Section 4 of PDF spec)
        if (! Schema::hasTable('usage_counters')) {
            Schema::create('usage_counters', function (Blueprint $table) {
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('period', 7); // e.g. 2026-11
                $table->string('feature_key', 50);
                $table->unsignedInteger('used')->default(0);
                $table->primary(['user_id', 'period', 'feature_key']);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_counters');
        Schema::dropIfExists('webhook_events');

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'subscription_id')) {
                $table->dropForeign(['subscription_id']);
                $table->dropColumn('subscription_id');
            }
            $table->dropColumn(['payment_type', 'failure_reason', 'invoice_number']);
        });

        Schema::dropIfExists('subscriptions');

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['cashfree_plan_id', 'interval_type', 'intervals', 'max_cycles']);
        });
    }
};
