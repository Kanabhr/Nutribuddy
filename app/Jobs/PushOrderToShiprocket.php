<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Shipping\ShipmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushOrderToShiprocket implements ShouldQueue
{
    use Queueable;

    public $order;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;

        // Wait until the checkout transaction has committed, so the order items exist when the job runs.
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(ShipmentService $shipments): void
    {
        $order = $this->order->fresh();

        // Don't push if the order is gone, cancelled, or already pushed
        if (! $order || $order->status === 'cancelled' || ! empty($order->shiprocket_order_id)) {
            return;
        }

        try {
            $shipments->push($order);

            Log::info("Order {$order->order_number} successfully pushed to Shiprocket.");
        } catch (Throwable $e) {
            Log::error("Failed to push Order {$order->order_number} to Shiprocket.", [
                'error' => $e->getMessage(),
            ]);

            // Never fail checkout because Shiprocket is down: retry later instead of throwing.
            $this->release(60);
        }
    }
}
