<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Notifications\OrderNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** Sends the customer notifications for one order event. Never fails a checkout or a webhook. */
class SendOrderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 2;

    public function __construct(public int $orderId, public string $event)
    {
        // Wait for the surrounding database transaction, so the order is fully saved first.
        $this->afterCommit();
    }

    public function handle(OrderNotificationService $service): void
    {
        $order = Order::find($this->orderId);

        if ($order) {
            $service->send($order, $this->event);
        }
    }
}