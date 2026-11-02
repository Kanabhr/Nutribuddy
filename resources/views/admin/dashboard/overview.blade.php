@extends('layouts.admin')

@php
    $title = 'Nutribuddy Admin Analytics';
    $subTitle = 'Performance Dashboard';

    $period = (int) ($filters['period'] ?? 7);
    $compare = $filters['compare'] ?? 'previous';
    $isCustomRange = (bool) ($filters['is_custom_range'] ?? false);

    $chartPayload = [
        'labels' => $revenueChart['labels'] ?? [],
        'revenue' => $revenueChart['revenue'] ?? [],
        'orders' => $revenueChart['orders'] ?? [],
        'aov' => $revenueChart['aov'] ?? [],
        'zones' => $zoneSales ?? [],
    ];

    $formatDelta = function ($value) {
        $value = (float) $value;
        $arrow = $value >= 0 ? 'ri-arrow-up-line' : 'ri-arrow-down-line';
        $class = $value >= 0 ? 'text-success-main bg-success-focus' : 'text-danger-main bg-danger-focus';
        return ['value' => abs($value), 'arrow' => $arrow, 'class' => $class];
    };

    $salesDelta = $formatDelta($deltas['sales'] ?? 0);
    $ordersDelta = $formatDelta($deltas['orders'] ?? 0);
    $aovDelta = $formatDelta($deltas['average_order_value'] ?? 0);
    $subscriptionDelta = $formatDelta($deltas['subscriptions'] ?? 0);

    $topProducts = collect($topSellingProducts ?? [])->take(5);
    $zoneColors = ['#2f5d3a', '#d6a32e', '#c1452b', '#3a6e8f'];

    $script = '<script>window.nbAdminDashboard = ' . json_encode($chartPayload) . ';</script>';
    $script .= <<<'SCRIPT'
<script>
document.addEventListener('DOMContentLoaded', function () {
  const data = window.nbAdminDashboard || {};
  const labels = data.labels || [];
  const revenue = data.revenue || [];
  const orders = data.orders || [];
  const aov = data.aov || [];
  const zones = data.zones || [];

  function money(value) {
    return '₹' + Number(value || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 });
  }

  function renderSpark(selector, series, color) {
    const el = document.querySelector(selector);
    if (!el || typeof ApexCharts === 'undefined') return;
    new ApexCharts(el, {
      series: [{ data: series.length ? series : [0] }],
      chart: { type: 'line', height: 48, sparkline: { enabled: true }, animations: { enabled: true } },
      colors: [color],
      stroke: { curve: 'smooth', width: 2 },
      tooltip: { enabled: false }
    }).render();
  }

  const trendEl = document.querySelector('#trendChart');
  let trendChart = null;
  const metricSeries = {
    revenue: { name: 'Revenue', data: revenue, color: '#2f5d3a', formatter: money },
    orders: { name: 'Orders', data: orders, color: '#3a6e8f', formatter: value => Number(value || 0).toLocaleString('en-IN') },
    aov: { name: 'AOV', data: aov, color: '#d6a32e', formatter: money }
  };

  function renderTrend(metric) {
    if (!trendEl || typeof ApexCharts === 'undefined') return;
    const cfg = metricSeries[metric] || metricSeries.revenue;
    const options = {
      series: [{ name: cfg.name, data: cfg.data }],
      chart: { type: metric === 'orders' ? 'bar' : 'area', height: 310, toolbar: { show: false }, zoom: { enabled: false } },
      colors: [cfg.color],
      dataLabels: { enabled: false },
      stroke: { curve: 'smooth', width: 3 },
      fill: { type: 'gradient', gradient: { opacityFrom: .28, opacityTo: .04 } },
      grid: { borderColor: '#eef1e9', strokeDashArray: 3 },
      plotOptions: { bar: { borderRadius: 6, columnWidth: '42%' } },
      xaxis: { categories: labels, labels: { style: { fontSize: '11px' } } },
      yaxis: { labels: { formatter: cfg.formatter } },
      tooltip: { y: { formatter: cfg.formatter } }
    };

    if (trendChart) {
      trendChart.updateOptions(options, true, true);
      return;
    }

    trendChart = new ApexCharts(trendEl, options);
    trendChart.render();
  }

  const zoneEl = document.querySelector('#zoneDoughnut');
  if (zoneEl && typeof ApexCharts !== 'undefined') {
    const zonePercents = zones.map(item => Number(item.percent || 0));
    new ApexCharts(zoneEl, {
      series: zonePercents.some(Boolean) ? zonePercents : [0, 0, 0, 0],
      chart: { type: 'donut', height: 250 },
      labels: zones.map(item => item.zone),
      colors: ['#2f5d3a', '#d6a32e', '#c1452b', '#3a6e8f'],
      legend: { show: false },
      dataLabels: { enabled: false },
      stroke: { width: 0 },
      plotOptions: { pie: { donut: { size: '68%' } } },
      tooltip: { y: { formatter: value => Number(value || 0).toFixed(1) + '%' } }
    }).render();
  }

  document.querySelectorAll('[data-dashboard-metric]').forEach(button => {
    button.addEventListener('click', function () {
      document.querySelectorAll('[data-dashboard-metric]').forEach(btn => btn.classList.remove('active'));
      this.classList.add('active');
      renderTrend(this.dataset.dashboardMetric);
    });
  });

  renderSpark('#sparkRevenue', revenue, '#2f5d3a');
  renderSpark('#sparkOrders', orders, '#3a6e8f');
  renderSpark('#sparkAov', aov, '#d6a32e');
  renderSpark('#sparkSubscriptions', [0, 0, 0, 0, 0, 0], '#c1452b');
  renderTrend('revenue');
});
</script>
SCRIPT;
@endphp

@section('content')
    <style>
        .nb-dashboard .card { border: 1px solid #eef1e9; box-shadow: 0 10px 28px rgba(18, 26, 21, .04); }
        .nb-dashboard .nb-filter-bar { align-items: center; background: #fff; border: 1px solid #eef1e9; border-radius: 12px; display: flex; flex-wrap: wrap; gap: 12px; padding: 14px; }
        .nb-dashboard .nb-seg { background: #f6f8f3; border: 1px solid #e6ecdf; border-radius: 10px; display: inline-flex; gap: 4px; padding: 4px; }
        .nb-dashboard .nb-seg a, .nb-dashboard .nb-seg button { border: 0; border-radius: 8px; color: #5f6a61; display: inline-flex; font-size: 12px; font-weight: 800; min-height: 34px; min-width: 48px; padding: 8px 12px; text-decoration: none; }
        .nb-dashboard .nb-seg .active { background: #fff; box-shadow: 0 5px 18px rgba(18, 26, 21, .08); color: #2f5d3a; }
        .nb-dashboard .nb-kpi-accent { border-radius: 8px 8px 0 0; height: 4px; left: 0; position: absolute; right: 0; top: 0; }
        .nb-dashboard .nb-kpi-card { min-height: 150px; overflow: hidden; position: relative; }
        .nb-dashboard .nb-kpi-spark { bottom: 12px; height: 48px; position: absolute; right: 12px; width: 96px; }
        .nb-dashboard .nb-chart-box { min-height: 310px; }
        .nb-dashboard .nb-zone-swatch { border-radius: 3px; display: inline-block; height: 10px; margin-right: 8px; width: 10px; }
        .nb-dashboard .nb-product-icon { align-items: center; background: linear-gradient(135deg, #e8f3e5, #fff4d8); border-radius: 10px; color: #2f5d3a; display: inline-flex; font-size: 12px; font-weight: 900; height: 42px; justify-content: center; width: 42px; }
        .nb-dashboard .nb-activity { border-left: 2px solid #e6ecdf; padding-left: 12px; }
        .nb-dashboard .nb-activity.is-done { border-left-color: #2f5d3a; }
        .nb-dashboard .nb-muted { color: #718076; }
        .nb-dashboard .nb-date-input { min-width: 150px; }
    </style>

    <div class="nb-dashboard">
        <form method="GET" action="{{ request()->url() }}" class="nb-filter-bar mb-24">
            <span class="text-sm fw-bold nb-muted">Period</span>
            <div class="nb-seg">
                @foreach ([7 => '7D', 30 => '30D', 90 => '90D', 365 => '12M'] as $value => $label)
                    <a href="{{ request()->fullUrlWithQuery(['period' => $value, 'compare' => $compare, 'start_date' => null, 'end_date' => null]) }}" class="{{ !$isCustomRange && $period === $value ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>

            <label class="text-sm fw-bold nb-muted ms-lg-2" for="start_date">Date range</label>
            <input type="date" name="start_date" id="start_date" class="form-control nb-date-input" value="{{ $filters['start_date'] }}">
            <span class="text-xs nb-muted">to</span>
            <input type="date" name="end_date" id="end_date" class="form-control nb-date-input" value="{{ $filters['end_date'] }}">

            <input type="hidden" name="period" value="{{ $period }}">
            <div class="ms-auto d-flex align-items-center gap-2">
                <span class="text-xs nb-muted">{{ \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') }} - {{ \Carbon\Carbon::parse($filters['end_date'])->format('d M Y') }}</span>
                <button type="submit" class="btn btn-primary-600 btn-sm d-inline-flex align-items-center gap-1">
                    <iconify-icon icon="solar:calendar-search-bold"></iconify-icon>
                    Apply
                </button>
                <a href="{{ route('admin.dashboard.export', request()->query()) }}" id="nbDashboardExport" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                    <iconify-icon icon="solar:download-minimalistic-bold"></iconify-icon>
                    Export
                </a>
            </div>
        </form>

        <div class="row gy-4">
            <div class="col-xxl-3 col-sm-6">
                <div class="card nb-kpi-card h-100">
                    <div class="nb-kpi-accent bg-success-main"></div>
                    <div class="card-body p-20">
                        <p class="text-sm nb-muted fw-bold mb-8">Revenue</p>
                        <h5 id="kpiRevenue" class="mb-8 fw-bold">₹{{ number_format($stats['sales_in_period'], 0) }}</h5>
                        <span class="badge {{ $salesDelta['class'] }} rounded-pill fw-bold">
                            <i class="{{ $salesDelta['arrow'] }}"></i> {{ number_format($salesDelta['value'], 1) }}%
                        </span>
                        <div class="nb-kpi-spark" id="sparkRevenue"></div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-3 col-sm-6">
                <div class="card nb-kpi-card h-100">
                    <div class="nb-kpi-accent bg-info-main"></div>
                    <div class="card-body p-20">
                        <p class="text-sm nb-muted fw-bold mb-8">Orders</p>
                        <h5 id="kpiOrders" class="mb-8 fw-bold">{{ number_format($stats['orders_in_period']) }}</h5>
                        <span class="badge {{ $ordersDelta['class'] }} rounded-pill fw-bold">
                            <i class="{{ $ordersDelta['arrow'] }}"></i> {{ number_format($ordersDelta['value'], 1) }}%
                        </span>
                        <div class="nb-kpi-spark" id="sparkOrders"></div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-3 col-sm-6">
                <div class="card nb-kpi-card h-100">
                    <div class="nb-kpi-accent bg-warning-main"></div>
                    <div class="card-body p-20">
                        <p class="text-sm nb-muted fw-bold mb-8">Avg. Order Value</p>
                        <h5 id="kpiAov" class="mb-8 fw-bold">₹{{ number_format($stats['average_order_value'], 0) }}</h5>
                        <span class="badge {{ $aovDelta['class'] }} rounded-pill fw-bold">
                            <i class="{{ $aovDelta['arrow'] }}"></i> {{ number_format($aovDelta['value'], 1) }}%
                        </span>
                        <div class="nb-kpi-spark" id="sparkAov"></div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-3 col-sm-6">
                <div class="card nb-kpi-card h-100">
                    <div class="nb-kpi-accent bg-danger-main"></div>
                    <div class="card-body p-20">
                        <p class="text-sm nb-muted fw-bold mb-8">Active Subscriptions</p>
                        <h5 id="kpiSubscriptions" class="mb-8 fw-bold">{{ number_format($stats['active_subscriptions']) }}</h5>
                        <span class="badge bg-secondary-100 text-secondary-600 rounded-pill fw-bold">Module not connected</span>
                        <div class="nb-kpi-spark" id="sparkSubscriptions"></div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-8">
                <div class="card h-100">
                    <div class="card-body p-24">
                        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-20">
                            <div>
                                <h6 class="mb-2 fw-bold">Sales &amp; Orders Trend</h6>
                                <p class="text-sm nb-muted mb-0">Real order and revenue data for selected period</p>
                            </div>
                            <div class="nb-seg">
                                <button type="button" class="active" data-dashboard-metric="revenue">Revenue</button>
                                <button type="button" data-dashboard-metric="orders">Orders</button>
                                <button type="button" data-dashboard-metric="aov">AOV</button>
                            </div>
                        </div>
                        <div id="trendChart" class="nb-chart-box"></div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-4">
                <div class="card h-100">
                    <div class="card-body p-24">
                        <div class="d-flex align-items-center justify-content-between mb-20">
                            <h6 class="mb-0 fw-bold">Today's Snapshot</h6>
                            <span class="badge bg-success-focus text-success-main rounded-pill">Live</span>
                        </div>
                        @foreach ([
                            'Orders today' => number_format($snapshot['orders_today']),
                            'Revenue today' => '₹' . number_format($snapshot['revenue_today'], 0),
                            'Pending fulfillment' => number_format($snapshot['pending_fulfillment']),
                            'In transit' => number_format($snapshot['in_transit']),
                            'Returns to process' => number_format($snapshot['returns_to_process']),
                            'Reviews awaiting reply' => number_format($snapshot['reviews_awaiting_reply']),
                        ] as $label => $value)
                            <div class="d-flex align-items-center justify-content-between py-10 border-bottom">
                                <span class="text-sm nb-muted">{{ $label }}</span>
                                <span class="fw-bold">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-xxl-4 col-lg-6">
                <div class="card h-100">
                    <div class="card-body p-24">
                        <div class="d-flex align-items-center justify-content-between mb-20">
                            <h6 class="mb-0 fw-bold">Top Products</h6>
                            <a href="{{ route('admin.ecommerce.products.index') }}" class="btn btn-sm btn-primary-light">View all</a>
                        </div>

                        @forelse ($topProducts as $product)
                            @php $initials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $product->product_name), 0, 2)); @endphp
                            <div class="d-flex align-items-center justify-content-between gap-3 py-10 border-bottom">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="nb-product-icon">{{ $initials ?: 'PR' }}</span>
                                    <div>
                                        <div class="fw-bold text-sm">{{ $product->product_name }}</div>
                                        <div class="text-xs nb-muted">{{ number_format($product->total_qty) }} sold</div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-sm">₹{{ number_format((float) $product->total_revenue, 0) }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="text-sm nb-muted">No paid product sales found for this period.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-xxl-4 col-lg-6">
                <div class="card h-100">
                    <div class="card-body p-24">
                        <div class="d-flex align-items-center justify-content-between mb-20">
                            <h6 class="mb-0 fw-bold">Sales by Zone</h6>
                            <span class="text-xs nb-muted">Selected period</span>
                        </div>
                        <div id="zoneDoughnut"></div>
                        <div class="row gy-2 mt-12">
                            @foreach (($zoneSales ?? []) as $index => $zone)
                                <div class="col-6 text-sm">
                                    <span class="nb-zone-swatch" style="background: {{ $zoneColors[$index] ?? '#2f5d3a' }}"></span>
                                    {{ $zone['zone'] }} {{ number_format($zone['percent'], 1) }}%
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-4">
                <div class="card h-100">
                    <div class="card-body p-24">
                        <div class="d-flex align-items-center justify-content-between mb-20">
                            <h6 class="mb-0 fw-bold">Recent Activity</h6>
                            <span class="text-xs nb-muted">Live feed</span>
                        </div>
                        <div class="d-flex flex-column gap-3">
                            @forelse (($recentOrders ?? collect())->take(5) as $order)
                                <div class="nb-activity is-done">
                                    <div class="text-xs nb-muted">{{ optional($order->created_at)->diffForHumans() }}</div>
                                    <div class="text-sm"><b>{{ $order->customer_name ?: ($order->user?->name ?? 'Customer') }}</b> placed an order - ₹{{ number_format((float) $order->grand_total, 0) }}</div>
                                </div>
                            @empty
                                <div class="text-sm nb-muted">No recent order activity yet.</div>
                            @endforelse

                            @if (($snapshot['returns_to_process'] ?? 0) > 0)
                                <div class="nb-activity">
                                    <div class="text-xs nb-muted">Now</div>
                                    <div class="text-sm">{{ number_format($snapshot['returns_to_process']) }} return request(s) waiting to process</div>
                                </div>
                            @endif

                            @if (($snapshot['reviews_awaiting_reply'] ?? 0) > 0)
                                <div class="nb-activity">
                                    <div class="text-xs nb-muted">Now</div>
                                    <div class="text-sm">{{ number_format($snapshot['reviews_awaiting_reply']) }} review(s) awaiting moderation</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
