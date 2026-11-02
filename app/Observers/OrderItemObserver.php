<?php

namespace App\Observers;

use App\Models\OrderItem;
use App\Services\StockService;

class OrderItemObserver
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Handle the OrderItem "created" event.
     */
    public function created(OrderItem $orderItem): void
    {
        $this->stockService->deductStock($orderItem);
    }

    /**
     * Handle the OrderItem "deleted" event.
     */
    public function deleted(OrderItem $orderItem): void
    {
        // If an order item is deleted, return the stock
        // But only if the order isn't already cancelled (to avoid double return)
        if ($orderItem->order && $orderItem->order->status !== 'cancelled') {
            $this->stockService->returnStock($orderItem);
        }
    }
}
