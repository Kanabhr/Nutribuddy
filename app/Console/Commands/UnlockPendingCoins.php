<?php

namespace App\Console\Commands;

use App\Services\Loyalty\CoinLedgerService;
use Illuminate\Console\Command;

/** R3: unlocks Pending coins once the return window after delivery has passed. Safe to run repeatedly. */
class UnlockPendingCoins extends Command
{
    protected $signature = 'coins:unlock-pending';

    protected $description = 'Move Pending coins to Available after delivery + return window';

    public function handle(CoinLedgerService $ledger): int
    {
        $r = $ledger->unlockDueCoins();
        $this->info("Unlocked {$r['unlocked']}, cancelled {$r['cancelled']}, still waiting {$r['waiting']}.");

        return self::SUCCESS;
    }
}
