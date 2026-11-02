<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Shipping\ShiprocketService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushOrderToShiprocket implements ShouldQueue
{
    use Queueable;

    public $order;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(ShiprocketService $shiprocket): void
    {
        // Don't push if already pushed
        if (!empty($this->order->shiprocket_order_id)) {
            return;
        }

        try {
            $response = $shiprocket->createOrder($this->order);
            
            $this->order->forceFill([
                'shiprocket_order_id' => $response['order_id'] ?? null,
                'shiprocket_shipment_id' => $response['shipment_id'] ?? null,
                'awb_code' => $response['awb_code'] ?? null,
            ])->save();

            Log::info("Order {$this->order->order_number} successfully pushed to Shiprocket.", [
                'response_data' => $response
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to push Order {$this->order->order_number} to Shiprocket.", [
                'error' => $e->getMessage()
            ]);
            
            // Release back to the queue for retry after 60 seconds
            $this->release(60);
        }
    }
}
