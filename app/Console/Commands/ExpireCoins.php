<?php

namespace App\Console\Commands;

use App\Services\Loyalty\CoinLedgerService;
use Illuminate\Console\Command;

/** R5: expires unspent coins whose expiry date has passed. Safe to run repeatedly. */
class ExpireCoins extends Command
{
    protected $signature = 'coins:expire';

    protected $description = 'Expire coins past their expiry date';

    public function handle(CoinLedgerService $ledger): int
    {
        $this->info('Expired coins: '.$ledger->expireDueCoins());

        return self::SUCCESS;
    }
}
