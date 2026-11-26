<?php

namespace App\Console\Commands;

use App\Models\CoinTransaction;
use App\Models\User;
use App\Services\Loyalty\CoinLedgerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * R5: run ONCE at go-live. Gives coins earned before the ledger existed an expiry of
 * (now + coin_expiry_months). Existing balances are not changed. Re-running does nothing.
 * The user's current balance is attributed to their newest legacy credit rows first.
 */
class BackfillLegacyCoins extends Command
{
    protected $signature = 'coins:backfill-legacy';

    protected $description = 'One-time: set expiry on coins earned before the ledger (go-live + 6 months)';

    public function handle(): int
    {
        $now = now();
        $expires = $now->copy()->addMonths(CoinLedgerService::expiryMonths());
        $users = 0;

        User::query()->where('coins_balance', '>', 0)->orderBy('id')->chunkById(200, function ($chunk) use ($now, $expires, &$users): void {
            foreach ($chunk as $user) {
                DB::transaction(function () use ($user, $now, $expires, &$users): void {
                    $left = (int) User::query()->whereKey($user->id)->lockForUpdate()->value('coins_balance');
                    $rows = CoinTransaction::query()
                        ->where('user_id', $user->id)
                        ->whereIn('type', ['earned', 'bonus', 'refunded'])
                        ->where('status', 'available')
                        ->whereNull('expires_at')
                        ->orderByDesc('id')
                        ->lockForUpdate()
                        ->get();
                    if ($rows->isEmpty()) {
                        return;
                    }
                    foreach ($rows as $row) {
                        $take = min($left, (int) $row->amount);
                        $left -= $take;
                        $row->forceFill([
                            'remaining' => $take,
                            'available_at' => $row->created_at ?? $now,
                            'expires_at' => $expires,
                        ])->save();
                    }
                    $users++;
                });
            }
        });

        $this->info("Backfilled coins for {$users} user(s). Expiry: {$expires->toDateTimeString()}");

        return self::SUCCESS;
    }
}
