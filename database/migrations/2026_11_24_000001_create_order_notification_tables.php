<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * DATABASE_RULES note (point 26)
 * - Reason: editable customer notification templates (email + WhatsApp) and a send log.
 * - Tables: notification_templates (new), notification_logs (new). No existing table is changed.
 * - Existing data: untouched. Default templates are inserted once (insertOrIgnore).
 *   EVERY default template is DISABLED, so nothing changes for customers until an admin switches it on
 *   (the senior will provide the approved wording and WhatsApp template names).
 * - Rollback: down() drops both tables, which deletes edited templates and the send log.
 * - Manual production steps: run this migration by hand, then open Admin > System > Notification templates
 *   and fill in the WhatsApp campaign names that exist in the AiSensy dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('event', 40);
            $table->string('channel', 20);
            $table->boolean('enabled')->default(false);
            $table->string('subject', 200)->nullable();
            $table->text('body')->nullable();
            $table->string('whatsapp_campaign', 100)->nullable();
            $table->json('whatsapp_params')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['event', 'channel'], 'notif_tpl_event_channel_unique');
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('event', 40);
            $table->string('channel', 20);
            $table->string('dedupe_key', 60)->default('');
            $table->string('status', 20)->default('sending'); // sending, sent, failed, skipped
            $table->string('recipient', 190)->nullable();
            $table->string('error', 500)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['order_id', 'event', 'channel', 'dedupe_key'], 'notif_log_unique');
            $table->index('created_at');
        });

        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
    }

    private function seedDefaults(): void
    {
        $now = now();
        $footer = "\n\nView your order: {order_link}\n\nTeam NutriBuddy";

        $email = [
            'order_confirmed' => ['Your order {order_number} is confirmed', "Hi {customer_name},\n\nThank you for your order! Your order {order_number} (total {order_total}) is confirmed and we are getting it ready.".$footer],
            'order_shipped' => ['Your order {order_number} has been shipped', "Hi {customer_name},\n\nGood news! Your order {order_number} is on its way.\n\n{tracking_line}".$footer],
            'order_delivered' => ['Your order {order_number} was delivered', "Hi {customer_name},\n\nYour order {order_number} has been delivered. We hope you enjoy it!".$footer],
            'order_cancelled' => ['Your order {order_number} has been cancelled', "Hi {customer_name},\n\nYour order {order_number} has been cancelled. If you paid online, the refund will follow as a separate message.".$footer],
            'order_refunded' => ['Refund update for order {order_number}', "Hi {customer_name},\n\nA refund of {refund_amount} has been processed for your order {order_number}. It can take a few business days to appear in your account.".$footer],
        ];

        $whatsapp = [
            'order_placed' => ['customer_name', 'order_number', 'order_total', 'order_link'],
            'order_confirmed' => ['customer_name', 'order_number', 'order_link'],
            'order_shipped' => ['customer_name', 'order_number', 'awb'],
            'order_delivered' => ['customer_name', 'order_number', 'order_link'],
            'order_refunded' => ['customer_name', 'order_number', 'refund_amount'],
            'order_cancelled' => ['customer_name', 'order_number'],
        ];

        foreach ($email as $event => [$subject, $body]) {
            DB::table('notification_templates')->insertOrIgnore([
                'event' => $event, 'channel' => 'email', 'enabled' => false,
                'subject' => $subject, 'body' => $body,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        foreach ($whatsapp as $event => $params) {
            DB::table('notification_templates')->insertOrIgnore([
                'event' => $event, 'channel' => 'whatsapp', 'enabled' => false,
                'whatsapp_campaign' => null, 'whatsapp_params' => json_encode($params),
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }
};