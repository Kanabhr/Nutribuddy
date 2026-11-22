<?php

namespace App\Http\Controllers\Admin\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Point 38 (Promotions): per-coupon usage and total discount given.
 * Counts non-cancelled orders that carry a coupon; discount comes from orders.discount_total (the coupon discount).
 */
class CouponReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $timezone = config('app.timezone');
        $from = ! empty($filters['from']) ? Carbon::createFromFormat('Y-m-d', $filters['from'], $timezone)->startOfDay() : null;
        $to = ! empty($filters['to']) ? Carbon::createFromFormat('Y-m-d', $filters['to'], $timezone)->endOfDay() : null;

        $base = fn () => Order::query()
            ->whereNotNull('coupon_id')
            ->where('status', '!=', 'cancelled')
            ->when($from, fn ($q) => $q->whereRaw('COALESCE(placed_at, created_at) >= ?', [$from->toDateTimeString()]))
            ->when($to, fn ($q) => $q->whereRaw('COALESCE(placed_at, created_at) <= ?', [$to->toDateTimeString()]));

        $perCoupon = $base()
            ->selectRaw('coupon_id, COUNT(*) as orders_count, COUNT(DISTINCT user_id) as customers_count, COALESCE(SUM(discount_total), 0) as total_discount, COALESCE(SUM(subtotal + tax_total), 0) as order_value')
            ->groupBy('coupon_id')
            ->get()
            ->keyBy('coupon_id');

        $totals = $base()
            ->selectRaw('COUNT(*) as orders_count, COUNT(DISTINCT user_id) as customers_count, COALESCE(SUM(discount_total), 0) as total_discount')
            ->first();

        $report = Coupon::orderBy('code')->get()->map(function (Coupon $coupon) use ($perCoupon) {
            $row = $perCoupon->get($coupon->id);
            $orders = (int) ($row->orders_count ?? 0);
            $totalDiscount = (float) ($row->total_discount ?? 0);
            $orderValue = (float) ($row->order_value ?? 0);

            return [
                'coupon' => $coupon,
                'orders' => $orders,
                'customers' => (int) ($row->customers_count ?? 0),
                'total_discount' => $totalDiscount,
                'average_discount' => $orders > 0 ? $totalDiscount / $orders : 0.0,
                // Discount as a share of the order value before discount (subtotal + tax).
                'discount_rate' => $orderValue > 0 ? ($totalDiscount / $orderValue) * 100 : 0.0,
                // Share of the coupon's global usage limit already consumed (uses the stored used_count).
                'limit_used' => $coupon->usage_limit ? min(100, ($coupon->used_count / $coupon->usage_limit) * 100) : null,
            ];
        })->sortByDesc('total_discount')->values();

        return view('admin.ecommerce.coupons.report', [
            'report' => $report,
            'totals' => $totals,
            'from' => $filters['from'] ?? '',
            'to' => $filters['to'] ?? '',
        ]);
    }
}
