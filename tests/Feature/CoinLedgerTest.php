<?php

namespace Tests\Feature;

use App\Events\OrderPaid;
use App\Models\CoinTransaction;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\OrderStatusHistory;
use App\Models\Setting;
use App\Models\User;
use App\Services\Loyalty\CoinLedgerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionProperty;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/** R2, R3, R5, R6, R7: pending coins, unlock after delivery + 7 days, expiry, FIFO spend, liability. */
class CoinLedgerTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $deliveredAt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->deliveredAt = Carbon::parse('2026-12-01 10:00:00');
        $property = new ReflectionProperty(Setting::class, 'cachedValues');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function ledger(): CoinLedgerService
    {
        return app(CoinLedgerService::class);
    }

    private function paidOrder(User $user, int $coins = 40, string $status = 'confirmed'): Order
    {
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => $status,
            'payment_status' => 'paid',
            'pricing_snapshot' => ['coins_earned' => $coins],
        ]);
        event(new OrderPaid($order));

        return $order;
    }

    private function markDelivered(Order $order, ?Carbon $at = null): void
    {
        $order->update(['status' => 'delivered']);
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => 'shipped',
            'to_status' => 'delivered',
        ]);
        OrderStatusHistory::query()->where('order_id', $order->id)->update(['created_at' => $at ?? $this->deliveredAt]);
    }

    private function lot(Order $order): CoinTransaction
    {
        return CoinTransaction::query()->where('order_id', $order->id)->where('type', 'earned')->firstOrFail();
    }

    // ---------------------------------------------------------------- R2

    public function test_paid_order_creates_one_pending_row_and_does_not_change_balance(): void
    {
        $user = User::factory()->create(['coins_balance' => 5]);
        $order = $this->paidOrder($user);
        event(new OrderPaid($order));

        $this->assertSame(1, CoinTransaction::query()->where('order_id', $order->id)->count());
        $this->assertSame('pending', $this->lot($order)->status);
        $this->assertSame(5, (int) $user->fresh()->coins_balance);
        $this->assertSame(40, $this->ledger()->pendingBalance($user));
    }

    // ---------------------------------------------------------------- R3

    public function test_coins_stay_pending_before_delivery(): void
    {
        $user = User::factory()->create();
        $order = $this->paidOrder($user);

        $r = $this->ledger()->unlockDueCoins($this->deliveredAt->copy()->addDays(30));

        $this->assertSame(1, $r['waiting']);
        $this->assertSame('pending', $this->lot($order)->status);
        $this->assertSame(0, (int) $user->fresh()->coins_balance);
    }

    public function test_unlock_boundary_exactly_seven_days_waits_and_one_second_later_unlocks(): void
    {
        $user = User::factory()->create();
        $order = $this->paidOrder($user);
        $this->markDelivered($order);

        $this->ledger()->unlockDueCoins($this->deliveredAt->copy()->addDays(7));
        $this->assertSame('pending', $this->lot($order)->status);

        $this->ledger()->unlockDueCoins($this->deliveredAt->copy()->addDays(7)->addSecond());
        $lot = $this->lot($order);
        $this->assertSame('available', $lot->status);
        $this->assertSame(40, (int) $lot->remaining);
        $this->assertSame(40, (int) $user->fresh()->coins_balance);
    }

    public function test_running_the_unlock_job_twice_credits_once(): void
    {
        $user = User::factory()->create();
        $order = $this->paidOrder($user);
        $this->markDelivered($order);
        $now = $this->deliveredAt->copy()->addDays(8);

        $this->ledger()->unlockDueCoins($now);
        $this->ledger()->unlockDueCoins($now);

        $this->assertSame(40, (int) $user->fresh()->coins_balance);
    }

    public function test_duplicate_delivered_history_rows_use_the_earliest(): void
    {
        $user = User::factory()->create();
        $order = $this->paidOrder($user);
        $this->markDelivered($order);
        $second = OrderStatusHistory::create(['order_id' => $order->id, 'from_status' => 'delivered', 'to_status' => 'delivered']);
        OrderStatusHistory::query()->whereKey($second->id)->update(['created_at' => $this->deliveredAt->copy()->addDays(5)]);

        $this->ledger()->unlockDueCoins($this->deliveredAt->copy()->addDays(7)->addSecond());

        $this->assertSame('available', $this->lot($order)->status);
    }

    public function test_cancelled_order_coins_are_cancelled_not_credited(): void
    {
        $user = User::factory()->create();
        $order = $this->paidOrder($user);
        $order->update(['status' => 'cancelled']);

        $r = $this->ledger()->unlockDueCoins($this->deliveredAt->copy()->addDays(30));

        $this->assertSame(1, $r['cancelled']);
        $this->assertSame('cancelled', $this->lot($order)->status);
        $this->assertSame(0, (int) $user->fresh()->coins_balance);
        $this->assertSame(0, $this->ledger()->pendingBalance($user));
    }

    public function test_open_return_keeps_coins_pending_and_rejected_return_releases_them(): void
    {
        $user = User::factory()->create();
        $order = $this->paidOrder($user);
        $this->markDelivered($order);
        $return = OrderReturn::create(['order_id' => $order->id, 'return_number' => 'RET-T1', 'reason' => 'x', 'status' => 'pending']);
        $now = $this->deliveredAt->copy()->addDays(9);

        $this->ledger()->unlockDueCoins($now);
        $this->assertSame('pending', $this->lot($order)->status);

        $return->update(['status' => 'rejected']);
        $this->ledger()->unlockDueCoins($now);
        $this->assertSame('available', $this->lot($order)->status);
    }

    // ---------------------------------------------------------------- R5 / R6

    private function availableLot(User $user, int $amount, Carbon $availableAt, ?Carbon $expiresAt = null): CoinTransaction
    {
        $user->increment('coins_balance', $amount);

        return CoinTransaction::create([
            'user_id' => $user->id, 'type' => 'earned', 'amount' => $amount, 'status' => 'available',
            'remaining' => $amount, 'available_at' => $availableAt,
            'expires_at' => $expiresAt ?? $availableAt->copy()->addMonths(6), 'description' => 'lot',
        ]);
    }

    public function test_unlocked_lot_expires_six_months_after_credit(): void
    {
        $user = User::factory()->create();
        $order = $this->paidOrder($user);
        $this->markDelivered($order);
        $now = $this->deliveredAt->copy()->addDays(8);
        $this->ledger()->unlockDueCoins($now);

        $this->assertTrue($this->lot($order)->expires_at->equalTo($now->copy()->addMonths(6)));
    }

    public function test_expiry_boundary_one_second_before_keeps_coins_and_at_expiry_removes_them(): void
    {
        $user = User::factory()->create();
        $expiry = Carbon::parse('2027-06-01 00:00:00');
        $this->availableLot($user, 30, Carbon::parse('2026-12-01'), $expiry);

        $this->assertSame(0, $this->ledger()->expireDueCoins($expiry->copy()->subSecond()));
        $this->assertSame(30, (int) $user->fresh()->coins_balance);

        $this->assertSame(30, $this->ledger()->expireDueCoins($expiry));
        $this->assertSame(0, (int) $user->fresh()->coins_balance);
        $this->assertDatabaseHas('coin_transactions', ['user_id' => $user->id, 'type' => 'spent', 'reason' => 'expired', 'amount' => 30]);
    }

    public function test_expire_job_twice_expires_once(): void
    {
        $user = User::factory()->create();
        $expiry = Carbon::parse('2027-06-01');
        $this->availableLot($user, 30, Carbon::parse('2026-12-01'), $expiry);

        $this->ledger()->expireDueCoins($expiry->copy()->addDay());
        $this->ledger()->expireDueCoins($expiry->copy()->addDay());

        $this->assertSame(0, (int) $user->fresh()->coins_balance);
        $this->assertSame(1, CoinTransaction::query()->where('reason', 'expired')->count());
    }

    public function test_spending_uses_oldest_lot_first_and_only_the_unspent_part_expires(): void
    {
        $user = User::factory()->create();
        $old = $this->availableLot($user, 50, Carbon::parse('2026-01-01'), Carbon::parse('2026-12-01'));
        $new = $this->availableLot($user, 50, Carbon::parse('2026-06-01'), Carbon::parse('2027-06-01'));
        $order = Order::factory()->create(['user_id' => $user->id, 'coins_redeemed' => 70]);

        $this->ledger()->spend($user, 70, $order);

        $this->assertSame(0, (int) $old->fresh()->remaining);
        $this->assertSame(30, (int) $new->fresh()->remaining);
        $this->assertSame(30, (int) $user->fresh()->coins_balance);

        // Old lot had nothing left, so its expiry removes nothing. New lot expiry removes the remaining 30.
        $this->assertSame(0, $this->ledger()->expireDueCoins(Carbon::parse('2026-12-02')));
        $this->assertSame(30, $this->ledger()->expireDueCoins(Carbon::parse('2027-06-02')));
        $this->assertSame(0, (int) $user->fresh()->coins_balance);
    }

    public function test_expired_lot_is_skipped_by_spending(): void
    {
        $user = User::factory()->create();
        Carbon::setTestNow(Carbon::parse('2027-01-01'));
        $expired = $this->availableLot($user, 40, Carbon::parse('2026-01-01'), Carbon::parse('2026-12-01'));
        $valid = $this->availableLot($user, 40, Carbon::parse('2026-12-01'), Carbon::parse('2027-12-01'));
        $order = Order::factory()->create(['user_id' => $user->id, 'coins_redeemed' => 10]);

        $this->ledger()->spend($user, 10, $order);

        $this->assertSame(40, (int) $expired->fresh()->remaining);
        $this->assertSame(30, (int) $valid->fresh()->remaining);
    }

    public function test_pending_coins_cannot_be_spent(): void
    {
        $user = User::factory()->create(['coins_balance' => 0]);
        $order = $this->paidOrder($user);

        $this->assertSame(0, $this->ledger()->availableBalance($user));
        $this->assertSame(0, (int) $this->lot($order)->remaining);
    }

    // ---------------------------------------------------------------- consistency + R7

    public function test_balance_equals_sum_of_remaining_after_unlock_spend_and_expiry(): void
    {
        $user = User::factory()->create();
        $order = $this->paidOrder($user, 100);
        $this->markDelivered($order);
        $this->ledger()->unlockDueCoins($this->deliveredAt->copy()->addDays(8));
        $spendOrder = Order::factory()->create(['user_id' => $user->id, 'coins_redeemed' => 25]);
        $this->ledger()->spend($user, 25, $spendOrder);

        $this->assertSame(75, (int) $user->fresh()->coins_balance);
        $this->assertSame(75, (int) CoinTransaction::query()->where('user_id', $user->id)->sum('remaining'));

        $this->ledger()->expireDueCoins($this->deliveredAt->copy()->addYear());
        $this->assertSame(0, (int) $user->fresh()->coins_balance);
        $this->assertSame(0, (int) CoinTransaction::query()->where('user_id', $user->id)->sum('remaining'));
    }

    public function test_liability_summary_counts_available_plus_pending_with_rupee_value(): void
    {
        $a = User::factory()->create(['coins_balance' => 100]);
        $b = User::factory()->create(['coins_balance' => 50]);
        $this->paidOrder($b, 50); // pending 50

        $s = $this->ledger()->liabilitySummary();

        $this->assertSame(150, $s['available']);
        $this->assertSame(50, $s['pending']);
        $this->assertSame(200, $s['total']);
        $this->assertEquals(round(200 / $s['conversion_rate'], 2), $s['rupee_value']);
    }

    // ---------------------------------------------------------------- commands + pages

    public function test_artisan_commands_run(): void
    {
        $user = User::factory()->create();
        $order = $this->paidOrder($user);
        $this->markDelivered($order, Carbon::now()->subDays(10));

        $this->artisan('coins:unlock-pending')->assertSuccessful();
        $this->assertSame(40, (int) $user->fresh()->coins_balance);
        $this->artisan('coins:expire')->assertSuccessful();
    }

    public function test_backfill_gives_legacy_coins_an_expiry_once_and_keeps_the_balance(): void
    {
        $user = User::factory()->create(['coins_balance' => 30]);
        CoinTransaction::create(['user_id' => $user->id, 'type' => 'earned', 'amount' => 100, 'description' => 'old']);
        CoinTransaction::create(['user_id' => $user->id, 'type' => 'spent', 'amount' => 70, 'description' => 'old']);

        $this->artisan('coins:backfill-legacy')->assertSuccessful();
        $this->artisan('coins:backfill-legacy')->assertSuccessful();

        $row = CoinTransaction::query()->where('type', 'earned')->firstOrFail();
        $this->assertSame(30, (int) $row->remaining);
        $this->assertNotNull($row->expires_at);
        $this->assertSame(30, (int) $user->fresh()->coins_balance);
    }

    public function test_wallet_page_shows_available_and_pending(): void
    {
        $user = User::factory()->create(['coins_balance' => 12]);
        $this->paidOrder($user, 40);

        $this->actingAs($user)->get(route('wallet'))
            ->assertOk()->assertSee('Available Coins')->assertSee('Pending Coins')->assertSee('PENDING');
    }

    public function test_admin_transactions_page_shows_liability(): void
    {
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::create(['name' => 'view loyalty', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Loyalty viewer', 'guard_name' => 'admin']);
        $role->givePermissionTo('view loyalty');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);
        User::factory()->create(['coins_balance' => 100]);

        $this->actingAs($admin, 'admin')->get(route('admin.ecommerce.loyalty.transactions'))
            ->assertOk()->assertSee('Total coins owed')->assertSee('Pending coins');
    }
}