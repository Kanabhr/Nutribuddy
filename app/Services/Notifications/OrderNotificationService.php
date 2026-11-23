<?php

namespace App\Services\Notifications;

use App\Mail\OrderNotificationMail;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Order;
use App\Models\OrderPaymentAdjustment;
use App\Services\Shipping\ShipmentService;
use App\Support\NotificationEvents;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Sends customer notifications (email + WhatsApp) from editable templates.
 *
 * - One log row per order, event, channel (and refund number), so repeats are never sent twice.
 * - A failure on one channel is logged and never stops the other channel or the caller.
 */
class OrderNotificationService
{
    public function __construct(private readonly AiSensyService $aiSensy) {}

    /** True when an enabled email template replaces the old generic "status updated" email for this status. */
    public function handlesEmailForStatus(?string $status): bool
    {
        $event = NotificationEvents::forOrderStatus($status);

        return $event !== null && NotificationTemplate::query()
            ->where('event', $event)->where('channel', 'email')->where('enabled', true)->exists();
    }

    public function send(Order $order, string $event): void
    {
        if (! NotificationEvents::isKnown($event)) {
            return;
        }

        $values = $this->values($order);
        $key = $this->dedupeKey($order, $event);

        $templates = NotificationTemplate::query()->where('event', $event)->where('enabled', true)->get();

        foreach ($templates as $template) {
            $this->deliver($order, $event, $template, $values, $key);
        }
    }

    /** @param array<string, string> $values */
    private function deliver(Order $order, string $event, NotificationTemplate $template, array $values, string $key): void
    {
        $log = $this->claim($order, $event, $template->channel, $key);
        if (! $log) {
            return; // already sent, or being sent right now
        }

        try {
            [$status, $recipient, $error] = $template->channel === 'email'
                ? $this->sendEmail($order, $template, $values)
                : $this->sendWhatsapp($order, $template, $values, $event);
        } catch (\Throwable $e) {
            Log::warning('Order notification failed.', ['order_id' => $order->id, 'event' => $event, 'channel' => $template->channel, 'message' => $e->getMessage()]);
            [$status, $recipient, $error] = ['failed', null, $e->getMessage()];
        }

        $log->update(['status' => $status, 'recipient' => $recipient, 'error' => $error ? Str::limit($error, 480, '') : null]);
    }

    /** Reserve the (order, event, channel) slot. Returns null when it must not be sent again. */
    private function claim(Order $order, string $event, string $channel, string $key): ?NotificationLog
    {
        $attributes = ['order_id' => $order->id, 'event' => $event, 'channel' => $channel, 'dedupe_key' => $key];

        try {
            $log = NotificationLog::firstOrCreate($attributes, ['status' => 'sending']);
        } catch (QueryException) {
            return null; // another worker created it at the same moment
        }

        if ($log->wasRecentlyCreated) {
            return $log;
        }

        $stuck = $log->status === 'sending' && $log->created_at && $log->created_at->lt(now()->subMinutes(10));
        if ($log->status === 'failed' || $stuck) {
            $log->update(['status' => 'sending', 'error' => null]);

            return $log;
        }

        return null;
    }

    /**
     * @param  array<string, string>  $values
     * @return array{0: string, 1: ?string, 2: ?string} status, recipient, error
     */
    private function sendEmail(Order $order, NotificationTemplate $template, array $values): array
    {
        $to = trim((string) $order->customer_email);
        if ($to === '') {
            return ['skipped', null, 'Order has no customer email.'];
        }

        $subject = trim(preg_replace('/\s+/', ' ', $this->fill((string) $template->subject, $values)));
        if ($subject === '' || trim((string) $template->body) === '') {
            return ['skipped', $to, 'Template has no subject or body.'];
        }

        Mail::to($to)->send(new OrderNotificationMail($subject, $this->toHtml($this->fill((string) $template->body, $values))));

        return ['sent', $to, null];
    }

    /**
     * @param  array<string, string>  $values
     * @return array{0: string, 1: ?string, 2: ?string}
     */
    private function sendWhatsapp(Order $order, NotificationTemplate $template, array $values, string $event): array
    {
        $campaign = trim((string) $template->whatsapp_campaign);
        if ($campaign === '') {
            return ['skipped', null, 'No AiSensy campaign name saved.'];
        }
        if (! (bool) config('services.aisensy.enabled', true) || blank(config('services.aisensy.api_key'))) {
            return ['skipped', null, 'AiSensy is disabled or has no API key.'];
        }

        $phone = trim((string) ($order->customer_phone ?: $order->shipping_phone));
        if (preg_replace('/\D+/', '', $phone) === '') {
            return ['skipped', null, 'Order has no phone number.'];
        }

        // WhatsApp rejects empty template variables, so an empty value becomes a dash.
        $params = array_map(
            fn (string $key): string => ($values[$key] ?? '') !== '' ? $values[$key] : '-',
            (array) $template->whatsapp_params
        );

        $ok = $this->aiSensy->sendOrderTemplate($order, $campaign, $params, ['order_notification', $event]);

        return $ok ? ['sent', $phone, null] : ['failed', $phone, 'AiSensy rejected the message or was unreachable (see laravel.log).'];
    }

    /** Refunds can repeat (several partial refunds), so each refund gets its own slot. */
    private function dedupeKey(Order $order, string $event): string
    {
        if ($event !== NotificationEvents::REFUNDED) {
            return '';
        }

        $count = OrderPaymentAdjustment::query()
            ->where('order_id', $order->id)->whereIn('type', ['refund', 'partial_refund'])->count();

        return $order->payment_status.':'.$count;
    }

    /** @return array<string, string> */
    public function values(Order $order): array
    {
        $trackingUrl = ShipmentService::trackingUrl($order) ?? '';

        $lines = [];
        if (filled($order->courier_name)) {
            $lines[] = 'Courier: '.$order->courier_name;
        }
        if (filled($order->awb_code)) {
            $lines[] = 'Tracking number (AWB): '.$order->awb_code;
        }
        if ($trackingUrl !== '') {
            $lines[] = 'Track your shipment: '.$trackingUrl;
        }

        $refund = OrderPaymentAdjustment::query()
            ->where('order_id', $order->id)->whereIn('type', ['refund', 'partial_refund'])
            ->orderByDesc('id')->value('amount');

        return [
            'customer_name' => $order->customer_name ?: 'Customer',
            'order_number' => (string) $order->order_number,
            'order_total' => 'Rs. '.number_format((float) $order->grand_total, 2),
            'order_date' => Carbon::parse($order->placed_at ?? $order->created_at)->format('d M Y'),
            'order_link' => route('user.orders.detail-page', $order),
            'courier' => (string) $order->courier_name,
            'awb' => (string) $order->awb_code,
            'tracking_url' => $trackingUrl,
            'tracking_line' => $lines ? implode("\n", $lines) : 'Tracking details will be shared as soon as the courier picks up your order.',
            'refund_amount' => 'Rs. '.number_format((float) ($refund ?? $order->grand_total), 2),
        ];
    }

    /** @param array<string, string> $values */
    private function fill(string $text, array $values): string
    {
        $map = [];
        foreach ($values as $key => $value) {
            $map['{'.$key.'}'] = $value;
        }

        return strtr($text, $map);
    }

    /** Escape everything (customer names are untrusted), then keep line breaks and make links clickable. */
    private function toHtml(string $text): string
    {
        $escaped = e($text);
        $linked = preg_replace('~(https?://[^\s<]+)~', '<a href="$1">$1</a>', $escaped);

        return '<div style="font-family:Arial,sans-serif;font-size:15px;line-height:1.5;color:#222">'.nl2br((string) $linked).'</div>';
    }
}