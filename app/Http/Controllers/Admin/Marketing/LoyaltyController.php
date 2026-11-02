<?php

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Marketing\UpdateLoyaltySettingsRequest;
use App\Models\CoinTransaction;
use App\Models\Setting;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    public function settings()
    {
        $settings = [
            'loyalty_conversion_rate' => Setting::get('loyalty_conversion_rate', 10),
            'loyalty_discount_percentage' => Setting::get('loyalty_discount_percentage', 10),
            'loyalty_enabled' => Setting::get('loyalty_enabled', 1),
        ];

        return view('admin.marketing.loyalty.settings', compact('settings'));
    }

    public function updateSettings(UpdateLoyaltySettingsRequest $request)
    {
        Setting::set('loyalty_conversion_rate', $request->loyalty_conversion_rate);
        Setting::set('loyalty_discount_percentage', $request->loyalty_discount_percentage);
        Setting::set('loyalty_enabled', $request->loyalty_enabled);

        return back()->with('success', 'Loyalty settings updated successfully.');
    }

    public function transactions()
    {
        $transactions = CoinTransaction::with(['user', 'order'])->latest()->paginate(20);
        return view('admin.marketing.loyalty.transactions', compact('transactions'));
    }
}
