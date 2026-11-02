<?php

namespace Tests\Feature;

use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCoinsRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_coins_are_awarded_once_after_payment_is_confirmed(): void
    {
        $user = User::factory()->create(['coins_balance' => 10]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'pricing_snapshot' => ['coins_earned' => 40],
        ]);

        event(new OrderPaid($order));
        event(new OrderPaid($order));

        $this->assertSame(50, (int) $user->fresh()->coins_balance);
        $this->assertDatabaseHas('coin_transactions', [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'type' => 'earned',
            'amount' => 40,
        ]);
        $this->assertDatabaseCount('coin_transactions', 1);
    }

    public function test_coins_are_not_awarded_for_an_unpaid_order(): void
    {
        $user = User::factory()->create(['coins_balance' => 10]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'pricing_snapshot' => ['coins_earned' => 40],
        ]);

        event(new OrderPaid($order));

        $this->assertSame(10, (int) $user->fresh()->coins_balance);
        $this->assertDatabaseCount('coin_transactions', 0);
    }
}
