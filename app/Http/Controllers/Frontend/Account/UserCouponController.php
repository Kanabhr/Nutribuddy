<?php

namespace App\Http\Controllers\Frontend\Account;

use App\Http\Controllers\Controller;
use App\Models\CouponUsage;

class UserCouponController extends Controller
{
    public function index()
    {
        $couponUsages = CouponUsage::with(['coupon', 'order'])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('frontend.account.coupons', compact('couponUsages'));
    }
}
