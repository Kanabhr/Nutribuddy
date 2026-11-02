<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\StockService;

class OrderObserver
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        // Push COD orders to Shiprocket immediately upon creation
        if (in_array(strtolower($order->payment_method), ['cod', 'cash_on_delivery'])) {
            \App\Jobs\PushOrderToShiprocket::dispatch($order);
        }
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Push to Shiprocket when prepaid order is marked paid
        $isPrepaidPaid = $order->wasChanged('payment_status') && $order->payment_status === 'paid';
        $isCodConfirmed = $order->wasChanged('status') && $order->status === 'confirmed' && in_array(strtolower($order->payment_method), ['cod', 'cash_on_delivery']);

        // We already pushed COD on creation, but if it failed or was skipped, we can push on confirmed too
        if ($isPrepaidPaid || $isCodConfirmed) {
            \App\Jobs\PushOrderToShiprocket::dispatch($order);
        }
        // If order status changed to 'cancelled', return stock for all items
        if ($order->wasChanged('status') && $order->status === 'cancelled') {
            foreach ($order->items as $item) {
                $this->stockService->returnStock($item);
            }
        }

        // If a cancelled order is moved back to a non-cancelled status (rare, but good to handle)
        if ($order->wasChanged('status') && $order->getOriginal('status') === 'cancelled' && $order->status !== 'cancelled') {
            foreach ($order->items as $item) {
                $this->stockService->deductStock($item);
            }
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        // If order is deleted, we should return the stock (unless it was already cancelled)
        if ($order->status !== 'cancelled') {
            foreach ($order->items as $item) {
                $this->stockService->returnStock($item);
            }
        }
    }
}
