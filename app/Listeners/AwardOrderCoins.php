<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\CoinTransaction;
use App\Models\Order;
use App\Models\User;
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

            $coins = (int) data_get($order->pricing_snapshot, 'coins_earned', 0);
            if ($coins <= 0) {
                return;
            }

            $user = User::query()->whereKey($order->user_id)->lockForUpdate()->firstOrFail();
            $user->increment('coins_balance', $coins);

            CoinTransaction::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'type' => 'earned',
                'amount' => $coins,
                'description' => "Coins earned from purchase on order #{$order->order_number}",
            ]);
        });
    }
}
