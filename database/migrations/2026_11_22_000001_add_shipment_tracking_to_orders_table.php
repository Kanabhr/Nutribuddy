<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue 22: Shiprocket shipping details, tracking and NDR/RTO visibility.
 *
 * Reason: admins need courier, AWB status, label and a tracking history per order; customers need to see tracking.
 * Tables: orders (5 new nullable columns + 2 indexes), order_shipment_events (new table).
 * Existing data: nothing is rewritten; all new columns are nullable. Index creation on a large orders table is quick but run it off-peak.
 * Rollback: down() drops the indexes, the new columns and the events table, so tracking history saved after deploy is lost.
 * Production: the owner runs `php artisan migrate` manually. Never from AI or CI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('courier_name', 100)->nullable()->after('awb_code');
            $table->string('shiprocket_status', 60)->nullable()->after('courier_name');
            $table->timestamp('shiprocket_status_at')->nullable()->after('shiprocket_status');
            $table->timestamp('shiprocket_pickup_at')->nullable()->after('shiprocket_status_at');
            $table->text('shiprocket_label_url')->nullable()->after('shiprocket_pickup_at');

            $table->index('awb_code');
            $table->index('shiprocket_order_id');
        });

        Schema::create('order_shipment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('awb_code')->nullable();
            $table->string('status', 100);
            $table->text('activity')->nullable();
            $table->string('location')->nullable();
            $table->string('source', 20)->default('system'); // webhook | poll | admin | system
            $table->timestamp('occurred_at')->nullable();
            $table->string('event_hash', 40)->unique();
            $table->timestamps();

            $table->index(['order_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_shipment_events');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['awb_code']);
            $table->dropIndex(['shiprocket_order_id']);
            $table->dropColumn([
                'courier_name',
                'shiprocket_status',
                'shiprocket_status_at',
                'shiprocket_pickup_at',
                'shiprocket_label_url',
            ]);
        });
    }
};
