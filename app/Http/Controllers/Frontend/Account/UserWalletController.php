<?php

namespace App\Http\Controllers\Frontend\Account;

use App\Http\Controllers\Controller;
use App\Models\CoinTransaction;
use App\Services\Loyalty\CoinLedgerService;

class UserWalletController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $transactions = CoinTransaction::where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        $ledger = app(CoinLedgerService::class);
        $availableCoins = $ledger->availableBalance($user);
        $pendingCoins = $ledger->pendingBalance($user);

        return view('frontend.account.wallet', compact('user', 'transactions', 'availableCoins', 'pendingCoins'));
    }
}
