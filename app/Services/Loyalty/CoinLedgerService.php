<?php

namespace App\Services\Loyalty;

use App\Models\CoinTransaction;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\OrderStatusHistory;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * R2, R3, R5, R6, R7: coin ledger.
 *
 * - Coins are created as PENDING when payment is confirmed (visible, not usable).
 * - A scheduled job unlocks them once the return window after delivery has passed.
 * - Every unlocked lot expires N months after the credit date; a scheduled job expires what is unspent.
 * - users.coins_balance is the AVAILABLE balance (redemption uses it). Pending is summed from the ledger.
 * - Every write is guarded by a row lock plus a status or idempotency key, so jobs are safe to run twice.
 */
class CoinLedgerService
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_CANCELLED = 'cancelled';

    public const REASON_REDEEMED = 'redeemed';

    public const REASON_EXPIRED = 'expired';

    /** Lot types: rows that add coins. */
    private const CREDIT_TYPES = ['earned', 'bonus', 'refunded'];

    /** Days after delivery before Pending coins unlock (also the return window). Setting `return_window_days`. */
    public static function returnWindowDays(): int
    {
        $raw = Setting::get('return_window_days', 7);

        return is_numeric($raw) ? max(0, (int) $raw) : 7;
    }

    /** Months an unlocked lot stays valid. Setting `coin_expiry_months`. */
    public static function expiryMonths(): int
    {
        $raw = Setting::get('coin_expiry_months', 6);

        return is_numeric($raw) ? max(1, (int) $raw) : 6;
    }

    // ------------------------------------------------------------------ balances

    public function availableBalance(User $user): int
    {
        return (int) User::query()->whereKey($user->id)->value('coins_balance');
    }

    public function pendingBalance(User $user): int
    {
        return (int) CoinTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'earned')
            ->where('status', self::STATUS_PENDING)
            ->sum('amount');
    }

    /** R7: company coin liability. Total = Available + Pending, valued at the redemption rate. */
    public function liabilitySummary(): array
    {
        $available = (int) User::query()->sum('coins_balance');
        $pending = (int) CoinTransaction::query()
            ->where('type', 'earned')
            ->where('status', self::STATUS_PENDING)
            ->sum('amount');
        $rate = CoinRewardCalculator::conversionRate();
        $total = $available + $pending;

        return [
            'available' => $available,
            'pending' => $pending,
            'total' => $total,
            'conversion_rate' => $rate,
            'rupee_value' => round($total / $rate, 2),
        ];
    }

    // ------------------------------------------------------------------ R2: create pending

    /**
     * Creates the Pending earn row for a paid order. Safe to call twice (unique key per order).
     * Call inside a transaction that already holds the order lock.
     */
    public function createPendingForOrder(Order $order): ?CoinTransaction
    {
        $coins = (int) data_get($order->pricing_snapshot, 'coins_earned', 0);
        if ($coins <= 0) {
            return null;
        }

        $key = 'earn:'.$order->id;
        $existing = CoinTransaction::query()->where('idempotency_key', $key)->first();
        if ($existing) {
            return $existing;
        }

        return CoinTransaction::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'type' => 'earned',
            'amount' => $coins,
            'status' => self::STATUS_PENDING,
            'remaining' => 0,
            'idempotency_key' => $key,
            'description' => "Coins earned from purchase on order #{$order->order_number} (unlock after return window)",
        ]);
    }

    // ------------------------------------------------------------------ R3: unlock

    /** Earliest time the order reached 'delivered' (re-saved statuses write duplicate rows). */
    public function deliveredAt(Order $order): ?Carbon
    {
        $value = OrderStatusHistory::query()
            ->where('order_id', $order->id)
            ->where('to_status', 'delivered')
            ->min('created_at');

        return $value ? Carbon::parse($value) : null;
    }

    /** @return array{unlocked:int, cancelled:int, waiting:int} */
    public function unlockDueCoins(?Carbon $now = null): array
    {
        $now ??= Carbon::now();
        $result = ['unlocked' => 0, 'cancelled' => 0, 'waiting' => 0];

        CoinTransaction::query()
            ->where('type', 'earned')
            ->where('status', self::STATUS_PENDING)
            ->orderBy('id')
            ->chunkById(200, function ($lots) use ($now, &$result): void {
                foreach ($lots as $lot) {
                    $outcome = $this->unlockLot((int) $lot->id, $now);
                    if (isset($result[$outcome])) {
                        $result[$outcome]++;
                    }
                }
            });

        return $result;
    }

    /** @return 'unlocked'|'cancelled'|'waiting'|'skipped' */
    public function unlockLot(int $lotId, Carbon $now): string
    {
        return DB::transaction(function () use ($lotId, $now): string {
            $lot = CoinTransaction::query()->whereKey($lotId)->lockForUpdate()->first();
            if (! $lot || $lot->type !== 'earned' || $lot->status !== self::STATUS_PENDING) {
                return 'skipped';
            }

            $order = $lot->order_id ? Order::query()->find($lot->order_id) : null;
            if (! $order || in_array($order->status, ['cancelled', 'returned'], true)) {
                $lot->update(['status' => self::STATUS_CANCELLED]);

                return 'cancelled';
            }

            $deliveredAt = $this->deliveredAt($order);
            if (! $deliveredAt) {
                return 'waiting';
            }

            // Returns are allowed up to and including delivered + window, so coins unlock strictly after it.
            if (! $now->gt($deliveredAt->copy()->addDays(self::returnWindowDays()))) {
                return 'waiting';
            }

            // An open return keeps coins Pending until the admin decides it.
            $hasOpenReturn = OrderReturn::query()
                ->where('order_id', $order->id)
                ->whereIn('status', ['pending', 'approved'])
                ->exists();
            if ($hasOpenReturn) {
                return 'waiting';
            }

            $user = User::query()->whereKey($lot->user_id)->lockForUpdate()->firstOrFail();

            $lot->update([
                'status' => self::STATUS_AVAILABLE,
                'remaining' => (int) $lot->amount,
                'available_at' => $now,
                'expires_at' => $now->copy()->addMonths(self::expiryMonths()),
                'description' => "Coins earned from purchase on order #{$order->order_number}",
            ]);
            $user->increment('coins_balance', (int) $lot->amount);

            return 'unlocked';
        });
    }

    // ------------------------------------------------------------------ R4: returns

    /** R4: a return is allowed up to and including delivered + window. Orders with no delivered history are not blocked. */
    public function isWithinReturnWindow(Order $order, ?Carbon $now = null): bool
    {
        $deliveredAt = $this->deliveredAt($order);
        if (! $deliveredAt) {
            return true;
        }

        return ! ($now ?? Carbon::now())->gt($deliveredAt->copy()->addDays(self::returnWindowDays()));
    }

    /**
     * R4: cancels Pending coins in proportion to the value of COMPLETED returns (products only; shipping and
     * COD fee are not part of the ratio). Cumulative and idempotent: safe to call after every return decision.
     * Coins already Available are not touched. Returns the coins cancelled in this call.
     */
    public function cancelPendingForReturns(Order $order): int
    {
        return DB::transaction(function () use ($order): int {
            $lot = CoinTransaction::query()
                ->where('order_id', $order->id)
                ->where('type', 'earned')
                ->where('status', self::STATUS_PENDING)
                ->lockForUpdate()
                ->first();
            if (! $lot) {
                return 0;
            }

            $original = (int) data_get($order->pricing_snapshot, 'coins_earned', (int) $lot->amount);
            $orderValue = (float) $order->items()->sum('line_total');
            $returnedValue = (float) \App\Models\OrderReturnItem::query()
                ->whereHas('returnRequest', fn ($q) => $q->where('status', 'completed')->where('order_id', $order->id))
                ->sum('line_total');
            if ($original <= 0 || $orderValue <= 0 || $returnedValue <= 0) {
                return 0;
            }

            $ratio = min(1.0, $returnedValue / $orderValue);
            $shouldBeCancelled = min($original, (int) round($original * $ratio));
            $alreadyCancelled = $original - (int) $lot->amount;
            $now = $shouldBeCancelled - $alreadyCancelled;
            if ($now <= 0) {
                return 0;
            }

            $newAmount = (int) $lot->amount - $now;
            $lot->update($newAmount > 0
                ? ['amount' => $newAmount, 'description' => $lot->description.' | '.$shouldBeCancelled.' coins cancelled for returned items']
                : ['amount' => 0, 'status' => self::STATUS_CANCELLED, 'description' => 'Coins cancelled: order fully returned']);

            return $now;
        });
    }

    // ------------------------------------------------------------------ redemption (R6: Available only, oldest first)

    /**
     * Takes coins from the user's balance, oldest unexpired lot first, and writes the 'spent' row.
     * Called from order placement (inside its transaction).
     */
    public function spend(User $user, int $coins, Order $order): void
    {
        if ($coins <= 0) {
            return;
        }

        User::query()->whereKey($user->id)->lockForUpdate()->first();

        $left = $coins;
        $now = Carbon::now();
        $lots = CoinTransaction::query()
            ->where('user_id', $user->id)
            ->whereIn('type', self::CREDIT_TYPES)
            ->where('status', self::STATUS_AVAILABLE)
            ->where('remaining', '>', 0)
            ->where(function ($query) use ($now): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->orderByRaw('COALESCE(available_at, created_at) asc')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($lots as $lot) {
            if ($left <= 0) {
                break;
            }
            $take = min((int) $lot->remaining, $left);
            $lot->decrement('remaining', $take);
            $left -= $take;
        }

        $user->decrement('coins_balance', $coins);

        CoinTransaction::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'type' => 'spent',
            'amount' => $coins,
            'status' => self::STATUS_AVAILABLE,
            'reason' => self::REASON_REDEEMED,
            'description' => "Coins redeemed for discount on order #{$order->order_number}",
        ]);
    }

    // ------------------------------------------------------------------ R5: expiry

    /** @return int total coins expired in this run */
    public function expireDueCoins(?Carbon $now = null): int
    {
        $now ??= Carbon::now();
        $expired = 0;

        CoinTransaction::query()
            ->whereIn('type', self::CREDIT_TYPES)
            ->where('status', self::STATUS_AVAILABLE)
            ->where('remaining', '>', 0)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->orderBy('id')
            ->chunkById(200, function ($lots) use ($now, &$expired): void {
                foreach ($lots as $lot) {
                    $expired += $this->expireLot((int) $lot->id, $now);
                }
            });

        return $expired;
    }

    /** Expires the unspent part of one lot. Returns coins expired (0 when nothing to do). */
    public function expireLot(int $lotId, Carbon $now): int
    {
        return DB::transaction(function () use ($lotId, $now): int {
            $lot = CoinTransaction::query()->whereKey($lotId)->lockForUpdate()->first();
            if (! $lot
                || $lot->status !== self::STATUS_AVAILABLE
                || (int) $lot->remaining <= 0
                || ! $lot->expires_at
                || $lot->expires_at->gt($now)) {
                return 0;
            }

            $user = User::query()->whereKey($lot->user_id)->lockForUpdate()->firstOrFail();
            $coins = min((int) $lot->remaining, max(0, (int) $user->coins_balance));

            $lot->update(['remaining' => 0]);

            if ($coins <= 0) {
                return 0;
            }

            $user->decrement('coins_balance', $coins);
            CoinTransaction::create([
                'user_id' => $user->id,
                'order_id' => $lot->order_id,
                'type' => 'spent',
                'amount' => $coins,
                'status' => self::STATUS_AVAILABLE,
                'reason' => self::REASON_EXPIRED,
                'idempotency_key' => 'expire:'.$lot->id,
                'description' => 'Coins expired',
            ]);

            return $coins;
        });
    }
}
