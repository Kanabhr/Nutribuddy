<?php

namespace App\Services\Admin\Dashboard;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardOverviewService
{
    public function dateRange(): array
    {
        $request = request();
        $compare = $request->get('compare', 'previous');
        if (! in_array($compare, ['previous', 'last_year'], true)) {
            $compare = 'previous';
        }

        $hasCustomRange = $request->filled('start_date') && $request->filled('end_date');
        if ($hasCustomRange) {
            $startDate = Carbon::parse($request->get('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->get('end_date'))->endOfDay();

            if ($startDate->gt($endDate)) {
                [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
            }

            $period = $startDate->diffInDays($endDate) + 1;
        } else {
            $period = (int) $request->integer('period', 7);
            if (! in_array($period, [7, 30, 90, 365], true)) {
                $period = 7;
            }

            $endDate = now()->endOfDay();
            $startDate = now()->subDays($period - 1)->startOfDay();
        }

        return [$startDate, $endDate, $period, $compare, $hasCustomRange];
    }

    public function stats(): array
    {
        [$startDate, $endDate, $period, $compare, $hasCustomRange] = $this->dateRange();

        $previousStartDate = $compare === 'last_year'
            ? $startDate->copy()->subYear()
            : $startDate->copy()->subDays($period);
        $previousEndDate = $compare === 'last_year'
            ? $endDate->copy()->subYear()
            : $startDate->copy()->subSecond();

        $weekStart = now()->startOfWeek();
        $customerScope = fn ($query) => $query->where(function ($userQuery) {
            $userQuery->whereNull('role')->orWhere('role', '!=', 'admin');
        });

        // Basic Stats
        $totalProducts = Product::count();
        $productsThisWeek = Product::where('created_at', '>=', $weekStart)->count();

        $totalCustomers = User::where($customerScope)->count();
        $customersThisWeek = User::where($customerScope)->where('created_at', '>=', $weekStart)->count();

        $totalOrders = Order::count();
        $ordersInPeriod = Order::whereBetween('created_at', [$startDate, $endDate])->count();
        $ordersThisWeek = Order::where('created_at', '>=', $weekStart)->count();

        $totalSales = (float) Order::where('payment_status', 'paid')->sum('grand_total');
        $salesInPeriod = (float) Order::where('payment_status', 'paid')->whereBetween('created_at', [$startDate, $endDate])->sum('grand_total');
        $salesThisWeek = (float) Order::where('payment_status', 'paid')->where('created_at', '>=', $weekStart)->sum('grand_total');
        $previousSales = (float) Order::where('payment_status', 'paid')->whereBetween('created_at', [$previousStartDate, $previousEndDate])->sum('grand_total');
        $previousOrders = Order::whereBetween('created_at', [$previousStartDate, $previousEndDate])->count();
        $averageOrderValue = $ordersInPeriod > 0 ? $salesInPeriod / $ordersInPeriod : 0;
        $previousAverageOrderValue = $previousOrders > 0 ? $previousSales / $previousOrders : 0;

        $totalExpense = (float) OrderReturn::whereIn('status', ['approved', 'completed'])->sum('refund_amount');
        $expenseInPeriod = (float) OrderReturn::whereIn('status', ['approved', 'completed'])->whereBetween('created_at', [$startDate, $endDate])->sum('refund_amount');
        $expenseThisWeek = (float) OrderReturn::whereIn('status', ['approved', 'completed'])->where('created_at', '>=', $weekStart)->sum('refund_amount');

        // Top Selling Products
        $topSellingProducts = OrderItem::select('product_id', 'product_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(line_total) as total_revenue'))
            ->whereHas('order', function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate])->where('payment_status', 'paid');
            })
            ->groupBy('product_id', 'product_name')
            ->orderBy('total_qty', 'desc')
            ->take(5)
            ->get();

        // Return Analysis
        $returnCount = OrderReturn::whereBetween('created_at', [$startDate, $endDate])->count();
        $returnRate = $ordersInPeriod > 0 ? ($returnCount / $ordersInPeriod) * 100 : 0;

        $bucketByMonth = $period > 120;
        $periodBuckets = $bucketByMonth
            ? collect()->range(0, $startDate->copy()->startOfMonth()->diffInMonths($endDate->copy()->startOfMonth()))
                ->map(fn ($offset) => $startDate->copy()->startOfMonth()->addMonths($offset))
            : collect()->range(0, $period - 1)
                ->map(fn ($offset) => $startDate->copy()->addDays($offset)->startOfDay());

        $revenueLabels = $periodBuckets->map(fn (Carbon $bucket) => $bucketByMonth ? $bucket->format('M Y') : $bucket->format('d M'))->values()->all();
        $bucketExpression = $this->bucketExpression($bucketByMonth);
        $orderBuckets = Order::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("{$bucketExpression} as bucket, COUNT(*) as order_count, SUM(CASE WHEN payment_status = ? THEN grand_total ELSE 0 END) as revenue", ['paid'])
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');
        $returnBuckets = OrderReturn::query()
            ->whereIn('status', ['approved', 'completed'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("{$bucketExpression} as bucket, SUM(refund_amount) as expense")
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        $bucketKeys = $periodBuckets->map(fn (Carbon $bucket) => $bucketByMonth ? $bucket->format('Y-m') : $bucket->toDateString());
        $revenueSeries = $bucketKeys->map(fn ($key) => (float) ($orderBuckets->get($key)->revenue ?? 0))->all();
        $orderCountSeries = $bucketKeys->map(fn ($key) => (int) ($orderBuckets->get($key)->order_count ?? 0))->all();
        $expenseSeries = $bucketKeys->map(fn ($key) => (float) ($returnBuckets->get($key)->expense ?? 0))->all();

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $ordersToday = Order::whereBetween('created_at', [$todayStart, $todayEnd])->count();
        $revenueToday = (float) Order::where('payment_status', 'paid')->whereBetween('created_at', [$todayStart, $todayEnd])->sum('grand_total');
        $pendingFulfillment = Order::whereIn('status', ['pending', 'confirmed', 'processing', 'packed'])->count();
        $inTransit = Order::whereIn('status', ['shipped', 'out_for_delivery'])->count();
        $reviewsAwaitingReply = ProductReview::where('is_active', false)->count();
        $returnsToProcess = OrderReturn::where('status', 'pending')->count();

        $zoneSales = $this->zoneSales($startDate, $endDate);
        $recentOrders = Order::with(['items', 'user'])->latest()->take(8)->get();

        return [
            'stats' => [
                'total_products' => $totalProducts,
                'products_this_week' => $productsThisWeek,
                'total_customers' => $totalCustomers,
                'customers_this_week' => $customersThisWeek,
                'total_orders' => $totalOrders,
                'orders_in_period' => $ordersInPeriod,
                'orders_this_week' => $ordersThisWeek,
                'total_sales' => $totalSales,
                'sales_in_period' => $salesInPeriod,
                'sales_this_week' => $salesThisWeek,
                'average_order_value' => $averageOrderValue,
                'total_expense' => $totalExpense,
                'expense_in_period' => $expenseInPeriod,
                'expense_this_week' => $expenseThisWeek,
                'return_count' => $returnCount,
                'return_rate' => $returnRate,
                'active_subscriptions' => 0,
                'subscriptions_this_week' => 0,
            ],
            'deltas' => [
                'sales' => $this->percentChange($salesInPeriod, $previousSales),
                'orders' => $this->percentChange($ordersInPeriod, $previousOrders),
                'average_order_value' => $this->percentChange($averageOrderValue, $previousAverageOrderValue),
                'subscriptions' => 0,
            ],
            'snapshot' => [
                'orders_today' => $ordersToday,
                'revenue_today' => $revenueToday,
                'pending_fulfillment' => $pendingFulfillment,
                'in_transit' => $inTransit,
                'returns_to_process' => $returnsToProcess,
                'reviews_awaiting_reply' => $reviewsAwaitingReply,
            ],
            'revenueChart' => [
                'labels' => $revenueLabels,
                'revenue' => $revenueSeries,
                'expense' => $expenseSeries,
                'orders' => $orderCountSeries,
                'aov' => collect($revenueSeries)->map(function ($revenue, $index) use ($orderCountSeries) {
                    $orders = (int) ($orderCountSeries[$index] ?? 0);
                    return $orders > 0 ? round((float) $revenue / $orders, 2) : 0;
                })->values()->all(),
            ],
            'zoneSales' => $zoneSales,
            'topSellingProducts' => $topSellingProducts,
            'recentOrders' => $recentOrders,
            'filters' => [
                'period' => $period,
                'compare' => $compare,
                'is_custom_range' => $hasCustomRange,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
        ];
    }

    private function percentChange(float|int $current, float|int $previous): float
    {
        if ((float) $previous === 0.0) {
            return (float) $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function bucketExpression(bool $byMonth): string
    {
        $driver = DB::connection()->getDriverName();

        if (! $byMonth) {
            return 'DATE(created_at)';
        }

        return match ($driver) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "TO_CHAR(DATE_TRUNC('month', created_at), 'YYYY-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };
    }

    private function zoneSales(Carbon $startDate, Carbon $endDate): array
    {
        $north = ['delhi', 'haryana', 'punjab', 'uttar pradesh', 'rajasthan', 'uttarakhand', 'himachal pradesh', 'jammu', 'kashmir', 'chandigarh'];
        $south = ['karnataka', 'tamil nadu', 'kerala', 'andhra pradesh', 'telangana', 'puducherry'];
        $west = ['maharashtra', 'gujarat', 'goa', 'madhya pradesh', 'chhattisgarh'];

        $totals = ['North' => 0.0, 'South' => 0.0, 'West' => 0.0, 'East' => 0.0];

        Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('shipping_state', DB::raw('SUM(grand_total) as total'))
            ->groupBy('shipping_state')
            ->get()
            ->each(function ($row) use (&$totals, $north, $south, $west) {
                $state = strtolower((string) $row->shipping_state);
                $zone = 'East';
                if (in_array($state, $north, true)) {
                    $zone = 'North';
                } elseif (in_array($state, $south, true)) {
                    $zone = 'South';
                } elseif (in_array($state, $west, true)) {
                    $zone = 'West';
                }

                $totals[$zone] += (float) $row->total;
            });

        $grandTotal = array_sum($totals);

        return collect($totals)->map(fn ($value, $zone) => [
            'zone' => $zone,
            'total' => $value,
            'percent' => $grandTotal > 0 ? round(($value / $grandTotal) * 100, 1) : 0,
        ])->values()->all();
    }
}
