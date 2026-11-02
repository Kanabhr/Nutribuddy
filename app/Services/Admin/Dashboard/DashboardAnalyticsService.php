<?php

namespace App\Services\Admin\Dashboard;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsService
{
    public function build(): array
    {
        $request = request();
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->get('start_date'))->startOfDay()
            : now()->startOfMonth();
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->get('end_date'))->endOfDay()
            : now()->endOfDay();

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $productId = $request->filled('product_id') ? (int) $request->get('product_id') : null;
        $segment = $request->get('segment', 'all');
        if (! in_array($segment, ['all', 'new', 'returning', 'subscribers'], true)) {
            $segment = 'all';
        }

        $ordersQuery = $this->analyticsOrdersQuery($startDate, $endDate, $productId, $segment);
        $allOrdersQuery = $this->analyticsOrdersQuery($startDate, $endDate, $productId, $segment, false);
        $allOrderStats = (clone $allOrdersQuery)->selectRaw('COUNT(*) as orders, COUNT(DISTINCT user_id) as customers, SUM(CASE WHEN user_id IS NULL THEN 1 ELSE 0 END) as guest_orders, SUM(discount_total) as discount, SUM(shipping_total) as shipping')->first();

        $stateRows = (clone $ordersQuery)->selectRaw("COALESCE(NULLIF(shipping_state, ''), 'Unknown') as state, SUM(grand_total) as revenue, COUNT(*) as orders")
            ->groupBy('shipping_state')->orderByDesc('revenue')->get()->map(function ($row) {
                $count = (int) $row->orders;
                $revenue = (float) $row->revenue;
                return ['state' => $row->state, 'zone' => $this->zoneForState($row->state), 'revenue' => $revenue, 'orders' => $count, 'aov' => $count ? round($revenue / $count, 2) : 0];
            })->values();
        $zoneTotals = collect(['North' => ['total' => 0.0, 'orders' => 0], 'South' => ['total' => 0.0, 'orders' => 0], 'West' => ['total' => 0.0, 'orders' => 0], 'East' => ['total' => 0.0, 'orders' => 0]]);
        foreach ($stateRows as $row) {
            $totals = $zoneTotals->get($row['zone']);
            $totals['total'] += $row['revenue'];
            $totals['orders'] += $row['orders'];
            $zoneTotals->put($row['zone'], $totals);
        }
        $zoneGrandTotal = (float) $stateRows->sum('revenue');
        $zones = $zoneTotals->map(fn ($totals, $zone) => [
            'zone' => $zone, 'key' => strtolower($zone), 'total' => $totals['total'],
            'percent' => $zoneGrandTotal > 0 ? round(($totals['total'] / $zoneGrandTotal) * 100, 1) : 0,
            'orders' => $totals['orders'],
            'states' => match ($zone) { 'North' => 'DL - UP - PB - HR', 'South' => 'KA - TN - TG - KL', 'West' => 'MH - GJ - RJ - GA', default => 'WB - OD - AS - BR' },
        ])->values();

        $cityRows = (clone $ordersQuery)->selectRaw("COALESCE(NULLIF(shipping_city, ''), 'Unknown') as city, SUM(grand_total) as revenue, COUNT(*) as orders")
            ->groupBy('shipping_city')->orderByDesc('revenue')->limit(8)->get()->map(fn ($row) => ['city' => $row->city, 'revenue' => (float) $row->revenue, 'orders' => (int) $row->orders])->values();

        $customerIds = (clone $ordersQuery)->whereNotNull('user_id')->select('user_id')->distinct();
        $genderRows = User::whereIn('id', $customerIds)
            ->selectRaw("COALESCE(NULLIF(gender, ''), 'Not set') as label, COUNT(*) as total")
            ->groupBy('label')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['label' => ucfirst((string) $row->label), 'total' => (int) $row->total])
            ->values();

        $ageBuckets = collect([
            'Under 18' => 0,
            '18-24' => 0,
            '25-34' => 0,
            '35-44' => 0,
            '45+' => 0,
            'Not set' => 0,
        ]);
        User::whereIn('id', (clone $customerIds))->get(['dob'])->each(function ($user) use (&$ageBuckets) {
            if (! $user->dob) {
                $ageBuckets->put('Not set', $ageBuckets->get('Not set', 0) + 1);
                return;
            }
            $age = Carbon::parse($user->dob)->age;
            $bucket = $age < 18 ? 'Under 18' : ($age <= 24 ? '18-24' : ($age <= 34 ? '25-34' : ($age <= 44 ? '35-44' : '45+')));
            $ageBuckets->put($bucket, $ageBuckets->get($bucket, 0) + 1);
        });

        $channelRows = (clone $ordersQuery)->selectRaw("COALESCE(NULLIF(UPPER(payment_method), ''), 'UNKNOWN') as label, SUM(grand_total) as total")
            ->groupBy('payment_method')->orderByDesc('total')->get()->map(fn ($row) => ['label' => $row->label, 'total' => (float) $row->total])->values();

        $paidStats = (clone $ordersQuery)->selectRaw('COUNT(*) as orders, SUM(grand_total) as revenue')->first();
        $paidRevenue = (float) $paidStats->revenue;
        $allOrderCount = (int) $allOrderStats->orders;
        $paidOrderCount = (int) $paidStats->orders;
        $uniqueCustomerCount = (int) $allOrderStats->customers;
        $guestOrderCount = (int) $allOrderStats->guest_orders;
        $totalDiscount = (float) $allOrderStats->discount;
        $totalShipping = (float) $allOrderStats->shipping;
        $averageOrderValue = $paidOrderCount > 0 ? round($paidRevenue / $paidOrderCount, 2) : 0;

        $productRows = OrderItem::select('product_id', 'product_name', DB::raw('SUM(quantity) as quantity'), DB::raw('SUM(line_total) as revenue'), DB::raw('COUNT(DISTINCT order_id) as orders'))
            ->whereHas('order', function ($query) use ($startDate, $endDate, $productId, $segment) {
                $this->applyAnalyticsOrderFilters($query, $startDate, $endDate, $productId, $segment, true);
            })
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('revenue')
            ->take(12)
            ->get()
            ->map(fn ($row) => [
                'product' => $row->product_name,
                'orders' => (int) $row->orders,
                'quantity' => (int) $row->quantity,
                'revenue' => (float) $row->revenue,
                'aov' => (int) $row->orders > 0 ? round((float) $row->revenue / (int) $row->orders, 2) : 0,
            ])
            ->values();

        $orderStatusRows = (clone $allOrdersQuery)->selectRaw("COALESCE(NULLIF(status, ''), 'unknown') as status, COUNT(*) as orders, SUM(CASE WHEN payment_status = 'paid' THEN grand_total ELSE 0 END) as revenue")
            ->groupBy('status')->orderByDesc('orders')->get()->map(fn ($row) => [
                'status' => ucfirst(str_replace('_', ' ', (string) $row->status)), 'orders' => (int) $row->orders, 'revenue' => (float) $row->revenue,
            ])->values();

        $paymentStatusRows = (clone $allOrdersQuery)->selectRaw("COALESCE(NULLIF(payment_status, ''), 'unknown') as payment_label, COUNT(*) as orders, SUM(grand_total) as amount")
            ->groupBy('payment_status')->orderByDesc('orders')->get()->map(fn ($row) => [
                'status' => ucfirst(str_replace('_', ' ', (string) $row->payment_label)), 'orders' => (int) $row->orders, 'amount' => (float) $row->amount,
            ])->values();

        $returnQuery = OrderReturn::with('order')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('order', function ($query) use ($startDate, $endDate, $productId, $segment) {
                $this->applyAnalyticsOrderFilters($query, $startDate, $endDate, $productId, $segment, false);
            });

        $returns = $returnQuery->get();
        $returnRows = $returns->groupBy(fn ($return) => $return->status ?: 'unknown')
            ->map(fn ($rows, $status) => [
                'status' => ucfirst(str_replace('_', ' ', (string) $status)),
                'returns' => $rows->count(),
                'refund' => (float) $rows->sum('refund_amount'),
            ])
            ->sortByDesc('returns')
            ->values();

        $returnReasonRows = $returns->groupBy(fn ($return) => $return->reason ?: 'Not specified')
            ->map(fn ($rows, $reason) => [
                'reason' => $reason,
                'returns' => $rows->count(),
                'refund' => (float) $rows->sum('refund_amount'),
            ])
            ->sortByDesc('returns')
            ->take(8)
            ->values();

        return [
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'product_id' => $productId,
                'segment' => $segment,
            ],
            'products' => Product::orderBy('name')->get(['id', 'name']),
            'zones' => $zones,
            'states' => $stateRows,
            'cities' => $cityRows,
            'gender' => $genderRows,
            'ages' => $ageBuckets->map(fn ($total, $label) => ['label' => $label, 'total' => $total])->values(),
            'channels' => $channelRows,
            'cohorts' => [],
            'summary' => [
                'orders' => $allOrderCount,
                'paid_orders' => $paidOrderCount,
                'revenue' => $paidRevenue,
                'average_order_value' => $averageOrderValue,
                'customers' => $uniqueCustomerCount,
                'guest_orders' => $guestOrderCount,
                'discount' => $totalDiscount,
                'shipping' => $totalShipping,
                'returns' => $returns->count(),
                'refunds' => (float) $returns->sum('refund_amount'),
                'return_rate' => $allOrderCount > 0 ? round(($returns->count() / $allOrderCount) * 100, 2) : 0,
            ],
            'productRows' => $productRows,
            'userRows' => [],
            'orderStatusRows' => $orderStatusRows,
            'paymentStatusRows' => $paymentStatusRows,
            'shipmentRows' => [],
            'returnRows' => $returnRows,
            'returnReasonRows' => $returnReasonRows,
        ];
    }

    /** Return one analytics table page using database aggregates and bounded results. */
    public function table(string $table): array
    {
        $request = request();
        $startDate = $request->filled('start_date') ? Carbon::parse($request->get('start_date'))->startOfDay() : now()->startOfMonth();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->get('end_date'))->endOfDay() : now()->endOfDay();
        if ($startDate->gt($endDate)) [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        $productId = $request->filled('product_id') ? (int) $request->get('product_id') : null;
        $segment = in_array($request->get('segment', 'all'), ['all', 'new', 'returning', 'subscribers'], true) ? $request->get('segment', 'all') : 'all';
        $start = max(0, (int) $request->get('start', 0));
        $length = min(100, max(1, (int) $request->get('length', 10)));
        $search = trim((string) data_get($request->all(), 'search.value', ''));
        $orderIndex = (int) data_get($request->all(), 'order.0.column', -1);
        $orderDirection = data_get($request->all(), 'order.0.dir') === 'asc' ? 'asc' : 'desc';

        if ($table === 'users') {
            $query = $this->analyticsOrdersQuery($startDate, $endDate, $productId, $segment, false)
                ->selectRaw("COALESCE(NULLIF(customer_name, ''), 'Guest') as customer, COALESCE(NULLIF(customer_email, ''), '-') as email, COUNT(*) as orders, SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_orders, SUM(CASE WHEN payment_status = 'paid' THEN grand_total ELSE 0 END) as revenue, MAX(created_at) as last_order")
                ->groupBy('user_id', 'customer_name', 'customer_email');
            if ($search !== '') $query->havingRaw('customer LIKE ? OR email LIKE ?', ['%'.$search.'%', '%'.$search.'%']);
            $filtered = DB::query()->fromSub(clone $query, 'analytics_users')->count();
            $total = DB::query()->fromSub($this->analyticsOrdersQuery($startDate, $endDate, $productId, $segment, false)
                ->select('user_id', 'customer_name', 'customer_email')->groupBy('user_id', 'customer_name', 'customer_email'), 'analytics_users')->count();
            $userOrderColumns = ['customer', 'email', 'orders', 'paid_orders', 'revenue', 'last_order'];
            $userOrderColumn = $userOrderColumns[$orderIndex] ?? null;
            $rows = $query->orderBy($userOrderColumn ?? 'revenue', $userOrderColumn ? $orderDirection : 'desc')->offset($start)->limit($length)->get()->map(fn ($row) => [
                'customer' => $row->customer, 'email' => $row->email, 'orders' => (int) $row->orders,
                'paid_orders' => (int) $row->paid_orders, 'revenue' => (float) $row->revenue,
                'last_order' => $row->last_order ? Carbon::parse($row->last_order)->format('d M Y') : '-',
            ])->all();
        } elseif ($table === 'shipments') {
            $query = $this->analyticsOrdersQuery($startDate, $endDate, $productId, $segment, false)
                ->selectRaw("COALESCE(NULLIF(fulfillment_status, ''), NULLIF(status, ''), 'unknown') as status, COUNT(*) as orders, SUM(shipping_total) as shipping, SUM(CASE WHEN payment_status = 'paid' THEN grand_total ELSE 0 END) as revenue")
                ->groupBy('fulfillment_status', 'status');
            if ($search !== '') $query->havingRaw('status LIKE ?', ['%'.$search.'%']);
            $filtered = DB::query()->fromSub(clone $query, 'analytics_shipments')->count();
            $total = DB::query()->fromSub($this->analyticsOrdersQuery($startDate, $endDate, $productId, $segment, false)
                ->select('fulfillment_status', 'status')->groupBy('fulfillment_status', 'status'), 'analytics_shipments')->count();
            $shipmentOrderColumns = ['status', 'orders', 'shipping', 'revenue'];
            $shipmentOrderColumn = $shipmentOrderColumns[$orderIndex] ?? null;
            $rows = $query->orderBy($shipmentOrderColumn ?? 'orders', $shipmentOrderColumn ? $orderDirection : 'desc')->offset($start)->limit($length)->get()->map(fn ($row) => [
                'status' => ucfirst(str_replace('_', ' ', (string) $row->status)), 'orders' => (int) $row->orders,
                'shipping' => (float) $row->shipping, 'revenue' => (float) $row->revenue,
            ])->all();
        } elseif ($table === 'cohorts') {
            $cohorts = $this->cohortRetention($startDate, $endDate, $productId);
            $total = count($cohorts);
            $filteredRows = $search === '' ? $cohorts : array_values(array_filter($cohorts, fn ($row) => stripos($row['cohort'], $search) !== false));
            $filtered = count($filteredRows);
            $rows = array_slice($filteredRows, $start, $length);
        } else {
            abort(404);
        }

        return ['draw' => (int) $request->get('draw', 1), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $rows];
    }

    private function analyticsOrdersQuery(Carbon $startDate, Carbon $endDate, ?int $productId, string $segment, bool $paidOnly = true): Builder
    {
        $query = Order::query();

        $this->applyAnalyticsOrderFilters($query, $startDate, $endDate, $productId, $segment, $paidOnly);

        return $query;
    }

    private function applyAnalyticsOrderFilters(Builder $query, Carbon $startDate, Carbon $endDate, ?int $productId, string $segment, bool $paidOnly = true): void
    {
        $query->whereBetween('created_at', [$startDate, $endDate]);

        if ($paidOnly) {
            $query->where('payment_status', 'paid');
        }

        if ($productId) {
            $query->whereHas('items', fn ($itemQuery) => $itemQuery->where('product_id', $productId));
        }

        if ($segment === 'new') {
            $query->whereDoesntHave('user.orders', fn ($orderQuery) => $orderQuery->where('created_at', '<', $startDate));
        } elseif ($segment === 'returning') {
            $query->whereHas('user.orders', fn ($orderQuery) => $orderQuery->where('created_at', '<', $startDate));
        } elseif ($segment === 'subscribers') {
            $query->whereRaw('1 = 0');
        }
    }

    private function zoneForState(?string $state): string
    {
        $state = strtolower(trim((string) $state));
        $north = ['delhi', 'haryana', 'punjab', 'uttar pradesh', 'rajasthan', 'uttarakhand', 'himachal pradesh', 'jammu', 'kashmir', 'chandigarh'];
        $south = ['karnataka', 'tamil nadu', 'kerala', 'andhra pradesh', 'telangana', 'puducherry'];
        $west = ['maharashtra', 'gujarat', 'goa', 'madhya pradesh', 'chhattisgarh'];

        if (in_array($state, $north, true)) return 'North';
        if (in_array($state, $south, true)) return 'South';
        if (in_array($state, $west, true)) return 'West';
        return 'East';
    }

    private function cohortRetention(Carbon $startDate, Carbon $endDate, ?int $productId): array
    {
        $baseQuery = Order::query()->where('payment_status', 'paid');
        if ($productId) {
            $baseQuery->whereHas('items', fn ($itemQuery) => $itemQuery->where('product_id', $productId));
        }

        return (clone $baseQuery)
            ->whereNotNull('user_id')
            ->whereBetween('created_at', [$startDate->copy()->subMonths(4), $endDate])
            ->select('user_id', DB::raw('MIN(created_at) as first_order_at'))
            ->groupBy('user_id')
            ->get()
            ->groupBy(fn ($row) => Carbon::parse($row->first_order_at)->format('M Y'))
            ->take(5)
            ->map(function ($rows, $cohort) use ($baseQuery) {
                $userIds = $rows->pluck('user_id');
                $start = Carbon::parse('01 ' . $cohort)->startOfMonth();

                $months = collect(range(0, 4))->map(function ($offset) use ($baseQuery, $userIds, $start) {
                    $monthStart = $start->copy()->addMonths($offset)->startOfMonth();
                    $monthEnd = $start->copy()->addMonths($offset)->endOfMonth();
                    $active = (clone $baseQuery)->whereIn('user_id', $userIds)->whereBetween('created_at', [$monthStart, $monthEnd])->distinct('user_id')->count('user_id');
                    return $userIds->count() > 0 ? round(($active / $userIds->count()) * 100) : null;
                })->all();

                return ['cohort' => $cohort, 'months' => $months];
            })->values()->all();
    }

}
