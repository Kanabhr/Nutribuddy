@extends('layouts.user-panel')
@section('title', 'NB Coins Wallet — NutriBuddy Kids')
@section('panel-page-class', 'panel-userdashboard panel-wallet')

@section('panel-content')
    <div class="ud-main">

        <div class="page">
            <!-- WALLET HEADER -->
       

            <!-- QUICK INFO -->
            <div class="stats-grid">
                <div class="stat-card" style="background: #fff; border: 2px solid var(--border);">
                    <div class="sc-icon" style="background: var(--mnl)">🪙</div>
                    <div class="sc-info">
                        <div class="num">{{ $transactions->where('type', 'earned')->sum('amount') }}</div>
                        <div class="lbl">Total Earned</div>
                    </div>
                </div>
                <div class="stat-card" style="background: #fff; border: 2px solid var(--border);">
                    <div class="sc-icon" style="background: #ffe4e6">💸</div>
                    <div class="sc-info">
                        <div class="num">{{ $transactions->where('type', 'spent')->sum('amount') }}</div>
                        <div class="lbl">Total Spent</div>
                    </div>
                </div>
            </div>

            <!-- TRANSACTION HISTORY -->
            <div class="box" style="margin-top: 24px;">
                <div class="box-head">
                    <h3>📜 Transaction History</h3>
                </div>
                <div style="overflow-x:auto">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $trx)
                                <tr>
                                    <td>{{ $trx->created_at->format('d M, Y') }}</td>
                                    <td style="font-size: 0.85rem; color: var(--text-light);">
                                        {{ $trx->description }}
                                        @if($trx->order)
                                            <br><small>Order: #{{ $trx->order->order_number }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="status-badge {{ $trx->type === 'earned' ? 's-delivered' : 's-cancelled' }}"
                                            style="font-size: 0.7rem;">
                                            {{ strtoupper($trx->type) }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong style="color: {{ $trx->type === 'earned' ? '#00a870' : '#e11d48' }};">
                                            {{ $trx->type === 'earned' ? '+' : '-' }} {{ $trx->amount }}
                                        </strong>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 40px; color: var(--text-light);">
                                        <div style="font-size: 2rem; margin-bottom: 10px;">🍃</div>
                                        No transactions yet. Start shopping to earn NB Coins!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div style="padding: 20px;">
                    {{ $transactions->links() }}
                </div>
            </div>

            <!-- HOW IT WORKS -->
            <div class="box" style="margin-top: 30px; background: #fcfdfe;">
                <div class="box-head">
                    <h3>💡 How NB Coins Work?</h3>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <h4>🛍️ Earn</h4>
                        <p>Earn coins on every purchase! Look for the "NB Coins" badge on product pages to see how many
                            you'll get automatically after your order is confirmed.</p>
                    </div>
                    <div class="info-item">
                        <h4>✨ Redeem</h4>
                        <p>Use your coins at checkout to get instant discounts. <strong>10 coins = ₹1</strong> off your
                            total amount. Just move the slider at checkout!</p>
                    </div>
                    <div class="info-item">
                        <h4>🛡️ Limits</h4>
                        <p>You can redeem coins for up to <strong>30%</strong> of your total order value. The more you shop,
                            the more you save on NutriBuddy!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
