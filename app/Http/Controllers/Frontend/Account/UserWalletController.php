<?php

namespace App\Http\Controllers\Frontend\Account;

use App\Http\Controllers\Controller;
use App\Models\CoinTransaction;

class UserWalletController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $transactions = CoinTransaction::where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        return view('frontend.account.wallet', compact('user', 'transactions'));
    }
}
