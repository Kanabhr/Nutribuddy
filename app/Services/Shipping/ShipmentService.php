<?php

namespace App\Services\Shipping;

use App\Models\Order;
use App\Models\OrderShipmentEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Order-side shipping workflow on top of ShiprocketService: pushes, AWB, pickup, label,
 * tracking history, cancellation, NDR actions and incoming tracking webhooks.
 *
 * It only touches shipping columns and order_shipment_events. It never changes the order's
 * own status or payment status; that stays in OrderWorkflowService.
 */
class ShipmentService
{
    public function __construct(private readonly ShiprocketService $shiprocket) {}

    /** Create the order on Shiprocket (same payload the queue job always sent). */
    public function push(Order $order): void
    {
        if ($order->status === 'cancelled') {
            throw new RuntimeException('Cancelled orders cannot be sent to Shiprocket.');
        }
        if (filled($order->shiprocket_order_id)) {
            throw new RuntimeException('This order is already in Shiprocket.');
        }

        $order->loadMissing('items');
        if ($order->items->isEmpty()) {
            throw new RuntimeException('This order has no items.');
        }

        $response = $this->shiprocket->createOrder($order);

        $shiprocketOrderId = $response['order_id'] ?? null;
        if (blank($shiprocketOrderId)) {
            throw new RuntimeException('Shiprocket did not return an order id.');
        }

        $order->forceFill([
            'shiprocket_order_id' => (string) $shiprocketOrderId,
            'shiprocket_shipment_id' => filled($response['shipment_id'] ?? null) ? (string) $response['shipment_id'] : null,
            'awb_code' => filled($response['awb_code'] ?? null) ? (string) $response['awb_code'] : $order->awb_code,
            'courier_name' => filled($response['courier_name'] ?? null) ? (string) $response['courier_name'] : $order->courier_name,
            'shiprocket_status' => 'ORDER CREATED',
            'shiprocket_status_at' => now(),
        ])->save();

        $this->recordEvent($order, [
            'status' => 'ORDER CREATED',
            'activity' => 'Order sent to Shiprocket',
            'occurred_at' => now(),
        ], 'system');
    }

    public function assignAwb(Order $order, ?int $courierId = null): void
    {
        $this->requireShipment($order);
        if (filled($order->awb_code)) {
            throw new RuntimeException('An AWB is already assigned to this order.');
        }

        $result = $this->shiprocket->assignAwb($order, $courierId);

        $order->forceFill([
            'awb_code' => $result['awb_code'],
            'courier_name' => $result['courier_name'] ?: $order->courier_name,
            'shiprocket_status' => 'AWB ASSIGNED',
            'shiprocket_status_at' => now(),
        ])->save();

        $this->recordEvent($order, [
            'status' => 'AWB ASSIGNED',
            'activity' => 'AWB '.$result['awb_code'].($result['courier_name'] ? ' assigned with '.$result['courier_name'] : ' assigned'),
            'occurred_at' => now(),
        ], 'admin');
    }

    public function schedulePickup(Order $order): void
    {
        $this->requireAwb($order);

        $result = $this->shiprocket->generatePickup($order);
        $scheduled = $this->parseDate($result['pickup_scheduled_date'] ?? null);

        $order->forceFill([
            'shiprocket_pickup_at' => $scheduled,
            'shiprocket_status' => 'PICKUP SCHEDULED',
            'shiprocket_status_at' => now(),
        ])->save();

        $this->recordEvent($order, [
            'status' => 'PICKUP SCHEDULED',
            'activity' => $scheduled ? 'Pickup scheduled for '.$scheduled->format('d M Y') : 'Pickup scheduled',
            'occurred_at' => now(),
        ], 'admin');
    }

    public function generateLabel(Order $order): string
    {
        $this->requireAwb($order);

        $url = $this->shiprocket->generateLabel($order);
        $order->forceFill(['shiprocket_label_url' => $url])->save();

        return $url;
    }

    /** Pull the latest tracking from Shiprocket. Returns how many new tracking steps were stored. */
    public function refreshTracking(Order $order): int
    {
        $this->requireAwb($order);

        $result = $this->shiprocket->track($order);
        $new = 0;
        $latest = null;

        foreach ($result['activities'] as $activity) {
            $at = $this->parseDate($activity['date'] ?? null);
            $status = $activity['sr-status-label'] ?? $activity['status'] ?? '';

            if ($this->recordEvent($order, [
                'awb_code' => $order->awb_code,
                'status' => is_string($status) ? $status : '',
                'activity' => $activity['activity'] ?? null,
                'location' => $activity['location'] ?? null,
                'occurred_at' => $at,
            ], 'poll')) {
                $new++;
            }

            if ($at && (! $latest || $at->gt($latest))) {
                $latest = $at;
            }
        }

        if ($result['status']) {
            $this->applyStatus($order, $result['status'], $latest ?? now(), null, quiet: true);
        }

        return $new;
    }

    public function cancel(Order $order): void
    {
        if (blank($order->shiprocket_order_id)) {
            throw new RuntimeException('This order was never sent to Shiprocket.');
        }
        if (strtoupper((string) $order->shiprocket_status) === 'CANCELED') {
            throw new RuntimeException('The Shiprocket order is already cancelled.');
        }
        if (self::classify($order->shiprocket_status) === 'delivered') {
            throw new RuntimeException('A delivered shipment cannot be cancelled.');
        }

        $this->shiprocket->cancelOrder($order);

        $order->forceFill([
            'shiprocket_status' => 'CANCELED',
            'shiprocket_status_at' => now(),
        ])->save();

        $this->recordEvent($order, [
            'status' => 'CANCELED',
            'activity' => 'Shipment cancelled on Shiprocket',
            'occurred_at' => now(),
        ], 'admin');
    }

    public function ndrAction(Order $order, string $action, ?string $comments = null, ?string $deferredDate = null): void
    {
        $this->requireAwb($order);

        if (! in_array($action, ['re-attempt', 'return'], true)) {
            throw new RuntimeException('Unknown NDR action.');
        }
        if (in_array(self::classify($order->shiprocket_status), ['delivered', 'cancelled'], true)) {
            throw new RuntimeException('This shipment is already closed.');
        }

        $this->shiprocket->ndrAction($order, $action, $comments, $deferredDate);

        $this->recordEvent($order, [
            'status' => $action === 'return' ? 'NDR: RETURN REQUESTED' : 'NDR: RE-ATTEMPT REQUESTED',
            'activity' => trim(($action === 'return' ? 'Return to origin requested' : 'Re-attempt requested')
                .($deferredDate ? ' for '.$deferredDate : '')
                .($comments ? ' - '.$comments : '')),
            'occurred_at' => now(),
        ], 'admin');
    }

    /**
     * Process an incoming Shiprocket tracking webhook. Returns the matched order, or null when ignored.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): ?Order
    {
        // Return shipments belong to the return flow, not the forward shipment shown here.
        if ((int) ($payload['is_return'] ?? 0) === 1) {
            return null;
        }

        $awb = trim((string) ($payload['awb'] ?? ''));
        $shiprocketOrderId = trim((string) ($payload['sr_order_id'] ?? ''));

        $order = null;
        if ($awb !== '') {
            $order = Order::where('awb_code', $awb)->first();
        }
        if (! $order && $shiprocketOrderId !== '') {
            $order = Order::where('shiprocket_order_id', $shiprocketOrderId)->first();
        }
        if (! $order) {
            Log::info('Tracking webhook for an unknown shipment.', ['awb' => $awb, 'sr_order_id' => $shiprocketOrderId]);

            return null;
        }

        $current = trim((string) ($payload['current_status'] ?? $payload['shipment_status'] ?? ''));
        $at = $this->parseDate($payload['current_timestamp'] ?? null) ?? now();
        $courier = is_string($payload['courier_name'] ?? null) ? $payload['courier_name'] : null;
        $eventAwb = $awb !== '' ? $awb : $order->awb_code;

        $scans = array_values(array_filter((array) ($payload['scans'] ?? []), 'is_array'));
        foreach ($scans as $scan) {
            $label = $scan['sr-status-label'] ?? $scan['status'] ?? '';
            $this->recordEvent($order, [
                'awb_code' => $eventAwb,
                'status' => is_string($label) ? $label : '',
                'activity' => $scan['activity'] ?? null,
                'location' => $scan['location'] ?? null,
                'occurred_at' => $this->parseDate($scan['date'] ?? null),
            ], 'webhook');
        }

        if ($scans === [] && $current !== '') {
            $this->recordEvent($order, [
                'awb_code' => $eventAwb,
                'status' => $current,
                'activity' => null,
                'occurred_at' => $at,
            ], 'webhook');
        }

        if (blank($order->awb_code) && $awb !== '') {
            $order->forceFill(['awb_code' => $awb]);
        }

        if ($current !== '') {
            $this->applyStatus($order, $current, $at, $courier, quiet: true);
        } else {
            $order->saveQuietly();
        }

        return $order;
    }

    /**
     * Store an event once. The hash makes repeated webhooks and tracking refreshes harmless.
     *
     * @param  array{awb_code?: ?string, status?: ?string, activity?: mixed, location?: mixed, occurred_at?: mixed}  $data
     * @return bool true when a new event was stored
     */
    public function recordEvent(Order $order, array $data, string $source = 'system'): bool
    {
        $status = Str::limit(trim((string) ($data['status'] ?? '')), 100, '');
        if ($status === '') {
            return false;
        }

        $activity = $this->clean($data['activity'] ?? null, 500);
        $location = $this->clean($data['location'] ?? null, 255);
        $awb = $data['awb_code'] ?? $order->awb_code;
        $occurredAt = $this->parseDate($data['occurred_at'] ?? null);

        $hash = sha1(implode('|', [
            $order->id,
            $awb,
            strtoupper($status),
            $activity,
            $occurredAt?->format('Y-m-d H:i:s'),
        ]));

        try {
            $event = OrderShipmentEvent::firstOrCreate(['event_hash' => $hash], [
                'order_id' => $order->id,
                'awb_code' => $awb,
                'status' => $status,
                'activity' => $activity,
                'location' => $location,
                'source' => $source,
                'occurred_at' => $occurredAt,
            ]);
        } catch (QueryException) {
            // Two requests stored the same event at the same moment; the other one won.
            return false;
        }

        return $event->wasRecentlyCreated;
    }

    /** Update the order's last known shipment status unless a newer one is already stored. */
    private function applyStatus(Order $order, string $status, Carbon $at, ?string $courier, bool $quiet): void
    {
        $currentAt = $order->shiprocket_status_at ? Carbon::parse($order->shiprocket_status_at) : null;

        if ($courier && blank($order->courier_name)) {
            $order->forceFill(['courier_name' => Str::limit($courier, 100, '')]);
        }

        if (! $currentAt || $at->gte($currentAt)) {
            $order->forceFill([
                'shiprocket_status' => Str::limit($status, 60, ''),
                'shiprocket_status_at' => $at,
            ]);
        }

        $quiet ? $order->saveQuietly() : $order->save();
    }

    /**
     * Group Shiprocket's free-text status into a few buckets.
     * Based on the status names in Shiprocket's docs: check against real webhook samples from your panel.
     */
    public static function classify(?string $status): string
    {
        $s = strtoupper(trim((string) $status));
        if ($s === '') {
            return 'unknown';
        }

        return match (true) {
            str_contains($s, 'RTO') => 'rto',
            str_contains($s, 'CANCEL') => 'cancelled',
            str_contains($s, 'UNDELIVERED'), str_contains($s, 'NDR') => 'ndr',
            str_contains($s, 'OUT FOR DELIVERY') => 'out_for_delivery',
            str_contains($s, 'DELIVERED') => 'delivered',
            str_contains($s, 'IN TRANSIT') => 'in_transit',
            str_contains($s, 'PICKED UP'), str_contains($s, 'SHIPPED') => 'picked_up',
            str_contains($s, 'PICKUP'), str_contains($s, 'AWB'), str_contains($s, 'MANIFEST'), str_contains($s, 'ORDER CREATED'), $s === 'NEW' => 'pickup_pending',
            default => 'other',
        };
    }

    /** Customer-friendly wording for a status bucket. */
    public static function customerLabel(?string $status): string
    {
        return match (self::classify($status)) {
            'delivered' => 'Delivered',
            'out_for_delivery' => 'Out for delivery',
            'in_transit' => 'In transit',
            'picked_up' => 'Picked up by the courier',
            'pickup_pending' => 'Preparing for pickup',
            'ndr' => 'Delivery attempt unsuccessful. We will contact you.',
            'rto' => 'Returning to the seller',
            'cancelled' => 'Shipment cancelled',
            'unknown' => 'Awaiting update',
            default => Str::title(strtolower((string) $status)),
        };
    }

    public static function trackingUrl(Order $order): ?string
    {
        return filled($order->awb_code)
            ? 'https://shiprocket.co/tracking/'.rawurlencode((string) $order->awb_code)
            : null;
    }

    private function requireShipment(Order $order): void
    {
        if (blank($order->shiprocket_shipment_id)) {
            throw new RuntimeException('Send the order to Shiprocket first.');
        }
    }

    private function requireAwb(Order $order): void
    {
        $this->requireShipment($order);
        if (blank($order->awb_code)) {
            throw new RuntimeException('Assign an AWB first.');
        }
    }

    private function clean(mixed $value, int $limit): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : Str::limit($value, $limit, '');
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $timezone = config('app.timezone');
        $value = trim($value);

        foreach (['d m Y H:i:s', 'Y-m-d H:i:s', 'Y-m-d H:i', 'd-m-Y H:i:s', 'Y-m-d'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value, $timezone);
                if ($parsed instanceof Carbon) {
                    return $parsed;
                }
            } catch (\Throwable) {
                // try the next format
            }
        }

        try {
            return Carbon::parse($value, $timezone);
        } catch (\Throwable) {
            return null;
        }
    }
}
