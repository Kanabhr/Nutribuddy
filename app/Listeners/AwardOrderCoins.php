<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\CoinTransaction;
use App\Models\Order;
use App\Services\Loyalty\CoinLedgerService;
use Illuminate\Support\Facades\DB;

class AwardOrderCoins
{
    public function handle(OrderPaid $event): void
    {
        DB::transaction(function () use ($event): void {
            $order = Order::query()->whereKey($event->order->id)->lockForUpdate()->firstOrFail();

            if ($order->payment_status !== 'paid' || $order->status === 'cancelled') {
                return;
            }

            if (CoinTransaction::query()->where('order_id', $order->id)->where('type', 'earned')->exists()) {
                return;
            }

            // R2: coins are created as Pending; the scheduled job unlocks them after delivery + return window.
            app(CoinLedgerService::class)->createPendingForOrder($order);
        });
    }
}
