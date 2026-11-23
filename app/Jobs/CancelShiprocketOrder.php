<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Shipping\ShipmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Cancels the Shiprocket order when the store order is cancelled, so a courier is not sent for it. */
class CancelShiprocketOrder implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    public function handle(ShipmentService $shipments): void
    {
        $order = $this->order->fresh();

        if (! $order || blank($order->shiprocket_order_id)) {
            return;
        }

        try {
            $shipments->cancel($order);
        } catch (Throwable $e) {
            Log::warning("Could not cancel Order {$order->order_number} on Shiprocket.", ['error' => $e->getMessage()]);

            // Make the failure visible on the order page: admin must cancel it in the Shiprocket panel.
            $shipments->recordEvent($order, [
                'status' => 'CANCEL FAILED',
                'activity' => 'Could not cancel on Shiprocket: '.$e->getMessage(),
                'occurred_at' => now(),
            ], 'system');
        }
    }
}
