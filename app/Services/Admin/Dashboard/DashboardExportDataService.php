<?php

namespace App\Services\Admin\Dashboard;

use App\Models\Order;
use Carbon\Carbon;

class DashboardExportDataService
{
    public function buildSheets(array $stats, Carbon $startDate, Carbon $endDate, int $period, string $compare, bool $hasCustomRange): array
    {
        $rangeLabel = $startDate->toDateString() . ' to ' . $endDate->toDateString();
        $filterType = $hasCustomRange ? 'Custom date range' : $period . ' days';
        $compareLabel = $compare === 'last_year' ? 'Last year' : 'Previous period';
        $chart = $stats['revenueChart'];
        $deltas = $stats['deltas'];
        $data = $stats['stats'];

        $overview = [
            ['NutriBuddy Dashboard Export'],
            ['Date Range', $rangeLabel],
            ['Filter Type', $filterType],
            ['Compare', $compareLabel],
            ['Generated At', now()->format('Y-m-d H:i:s')],
            [],
            ['Dashboard Card', 'Main Value', 'Change / Detail'],
            ['Revenue', (float) $data['sales_in_period'], number_format((float) $deltas['sales'], 1) . '%'],
            ['Orders', (int) $data['orders_in_period'], number_format((float) $deltas['orders'], 1) . '%'],
            ['Average Order Value', (float) $data['average_order_value'], number_format((float) $deltas['average_order_value'], 1) . '%'],
            ['Active Subscriptions', (int) $data['active_subscriptions'], 'Module not connected'],
            ['Returns', (int) $data['return_count'], number_format((float) $data['return_rate'], 2) . '% return rate'],
            ['Refund Expense', (float) $data['expense_in_period'], 'Approved and completed returns'],
        ];

        $periodRows = collect($chart['labels'])->map(fn ($label, $index) => [
            $label,
            (float) ($chart['revenue'][$index] ?? 0),
            (int) ($chart['orders'][$index] ?? 0),
            (float) ($chart['aov'][$index] ?? 0),
            (float) ($chart['expense'][$index] ?? 0),
        ])->values()->all();

        $statusRows = Order::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("COALESCE(NULLIF(status, ''), 'unknown') as status_label, COUNT(*) as orders, SUM(grand_total) as total")
            ->groupBy('status_label')
            ->orderByDesc('orders')
            ->get()
            ->map(fn ($row) => [ucfirst((string) $row->status_label), (int) $row->orders, (float) $row->total])
            ->values()
            ->all();

        $paymentRows = Order::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("COALESCE(NULLIF(payment_status, ''), 'unknown') as payment_label, COUNT(*) as orders, SUM(grand_total) as total")
            ->groupBy('payment_label')
            ->orderByDesc('orders')
            ->get()
            ->map(fn ($row) => [ucfirst((string) $row->payment_label), (int) $row->orders, (float) $row->total])
            ->values()
            ->all();

        $orderDetailRows = [];
        Order::with('items')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->chunk(200, function ($orders) use (&$orderDetailRows) {
                foreach ($orders as $order) {
                    $items = $order->items
                        ->map(fn ($item) => $item->product_name . ' x ' . $item->quantity)
                        ->implode('; ');

                    $orderDetailRows[] = [
                        $order->order_number,
                        optional($order->created_at)->format('Y-m-d H:i'),
                        $order->customer_name,
                        $order->customer_email,
                        $order->customer_phone,
                        $order->shipping_city,
                        $order->shipping_state,
                        $order->status,
                        $order->payment_status,
                        $order->payment_method,
                        $items,
                        (float) $order->subtotal,
                        (float) $order->discount_total,
                        (float) $order->shipping_total,
                        (float) ($order->gst_total ?: $order->tax_total),
                        (float) $order->grand_total,
                    ];
                }
            });

        return [
            'Overview' => $overview,
            'Revenue Card' => array_merge([
                ['Revenue Card'],
                ['Date Range', $rangeLabel],
                ['Total Revenue', (float) $data['sales_in_period']],
                ['Revenue Change', number_format((float) $deltas['sales'], 1) . '%'],
                ['Total Paid Sales All Time', (float) $data['total_sales']],
                [],
                ['Period', 'Revenue', 'Orders', 'Average Order Value', 'Refund Expense'],
            ], $periodRows),
            'Orders Card' => array_merge([
                ['Orders Card'],
                ['Date Range', $rangeLabel],
                ['Orders In Period', (int) $data['orders_in_period']],
                ['Orders Change', number_format((float) $deltas['orders'], 1) . '%'],
                ['Total Orders All Time', (int) $data['total_orders']],
                [],
                ['Status', 'Orders', 'Grand Total'],
            ], $statusRows, [
                [],
                ['Payment Status', 'Orders', 'Grand Total'],
            ], $paymentRows),
            'AOV Card' => array_merge([
                ['Average Order Value Card'],
                ['Date Range', $rangeLabel],
                ['Average Order Value', (float) $data['average_order_value']],
                ['AOV Change', number_format((float) $deltas['average_order_value'], 1) . '%'],
                [],
                ['Period', 'Revenue', 'Orders', 'Average Order Value', 'Refund Expense'],
            ], $periodRows),
            'Subscriptions Card' => [
                ['Active Subscriptions Card'],
                ['Date Range', $rangeLabel],
                ['Active Subscriptions', (int) $data['active_subscriptions']],
                ['Status', 'Module not connected'],
            ],
            'Snapshot Card' => [
                ["Today's Snapshot Card"],
                ['Metric', 'Value'],
                ['Orders Today', (int) $stats['snapshot']['orders_today']],
                ['Revenue Today', (float) $stats['snapshot']['revenue_today']],
                ['Pending Fulfillment', (int) $stats['snapshot']['pending_fulfillment']],
                ['In Transit', (int) $stats['snapshot']['in_transit']],
                ['Returns To Process', (int) $stats['snapshot']['returns_to_process']],
                ['Reviews Awaiting Reply', (int) $stats['snapshot']['reviews_awaiting_reply']],
            ],
            'Top Products Card' => array_merge([
                ['Top Products Card'],
                ['Date Range', $rangeLabel],
                ['Product', 'Quantity Sold', 'Revenue'],
            ], collect($stats['topSellingProducts'])->map(fn ($product) => [
                $product->product_name,
                (int) $product->total_qty,
                (float) $product->total_revenue,
            ])->values()->all()),
            'Sales By Zone Card' => array_merge([
                ['Sales By Zone Card'],
                ['Date Range', $rangeLabel],
                ['Zone', 'Revenue', 'Percent'],
            ], collect($stats['zoneSales'])->map(fn ($zone) => [
                $zone['zone'],
                (float) $zone['total'],
                (float) $zone['percent'],
            ])->values()->all()),
            'Recent Activity Card' => array_merge([
                ['Recent Activity Card'],
                ['Date', 'Customer', 'Activity', 'Amount'],
            ], collect($stats['recentOrders'])->take(8)->map(fn ($order) => [
                optional($order->created_at)->format('Y-m-d H:i'),
                $order->customer_name ?: ($order->user?->name ?? 'Customer'),
                'Placed an order',
                (float) $order->grand_total,
            ])->values()->all()),
            'Order Details' => array_merge([
                ['Order Details'],
                ['Date Range', $rangeLabel],
                [],
                ['Order No', 'Date', 'Customer', 'Email', 'Phone', 'City', 'State', 'Status', 'Payment Status', 'Payment Method', 'Items', 'Subtotal', 'Discount', 'Shipping', 'Tax/GST', 'Grand Total'],
            ], $orderDetailRows),
        ];
    }

}
