<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Notifications\OrderNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Sends the customer notifications for one order event.
 * A failed channel is retried up to 3 times after the first try, so 4 attempts in total (1, 5 and 15 minutes apart). It never throws, so a
 * provider problem can never break checkout, an order update or a webhook, even on the sync queue.
 */
class SendOrderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 4;

    public function __construct(public int $orderId, public string $event)
    {
        // Wait for the surrounding database transaction, so the order is fully saved first.
        $this->afterCommit();
    }

    public function handle(OrderNotificationService $service): void
    {
        $order = Order::find($this->orderId);
        if (! $order) {
            return;
        }

        $anyFailed = $service->send($order, $this->event);

        // Channels that already sent are skipped on the next attempt (one log row per channel).
        if ($anyFailed && $this->attempts() < $this->tries) {
            $this->release([1 => 60, 2 => 300, 3 => 900][$this->attempts()] ?? 900);
        }
    }
}