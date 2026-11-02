@extends('layouts.admin')

@php
    $title = 'Analytics & Demographics';
    $subTitle = 'Zone, customer and channel insights';

    $chartPayload = [
        'states' => $states,
        'gender' => $gender,
        'ages' => $ages,
        'channels' => $channels,
    ];

    $zoneClasses = [
        'north' => 'success',
        'south' => 'warning',
        'west' => 'danger',
        'east' => 'info',
    ];
    $zoneColors = [
        'north' => '#2f5d3a',
        'south' => '#d6a32e',
        'west' => '#c1452b',
        'east' => '#3a6e8f',
    ];
    $summary = $summary ?? [];

    $script = '<script>window.nbAnalyticsData = ' . json_encode($chartPayload) . ';</script>';
    $script .= <<<'SCRIPT'
<script>
document.addEventListener('DOMContentLoaded', function () {
  const data = window.nbAnalyticsData || {};
  let activeZone = 'all';
  let activeMetric = 'rev';

  function money(value) {
    return 'Rs. ' + Number(value || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 });
  }

  function metricValue(row, metric) {
    if (metric === 'ord') return Number(row.orders || 0);
    if (metric === 'aov') return Number(row.aov || 0);
    return Number(row.revenue || 0);
  }

  function formatMetric(value, metric) {
    return metric === 'ord' ? Number(value || 0).toLocaleString('en-IN') : money(value);
  }

  function renderStateBars() {
    const wrap = document.querySelector('#stateBars');
    const title = document.querySelector('#stateTitle');
    if (!wrap) return;

    const rows = (data.states || [])
      .filter(row => activeZone === 'all' || String(row.zone || '').toLowerCase() === activeZone)
      .sort((a, b) => metricValue(b, activeMetric) - metricValue(a, activeMetric))
      .slice(0, 10);

    if (title) title.textContent = activeZone === 'all' ? 'Top States - All Zones' : 'Top States - ' + activeZone.charAt(0).toUpperCase() + activeZone.slice(1) + ' Zone';

    const max = Math.max(1, ...rows.map(row => metricValue(row, activeMetric)));
    wrap.innerHTML = rows.length
      ? rows.map(row => {
          const value = metricValue(row, activeMetric);
          const width = Math.max(3, (value / max) * 100);
          return `<div class="nb-state-bar">
            <span class="nb-state-name">${row.state || 'Unknown'}</span>
            <div class="nb-state-track"><div style="width:${width}%"></div></div>
            <span class="nb-state-value">${formatMetric(value, activeMetric)}</span>
          </div>`;
        }).join('')
      : '<div class="text-sm text-secondary-light">No state data found for this filter.</div>';
  }

  document.querySelectorAll('[data-zone]').forEach(card => {
    card.addEventListener('click', function () {
      activeZone = this.dataset.zone;
      document.querySelectorAll('[data-zone]').forEach(item => item.classList.toggle('is-active', item === this));
      renderStateBars();
    });
  });

  document.querySelectorAll('[data-state-metric]').forEach(button => {
    button.addEventListener('click', function () {
      activeMetric = this.dataset.stateMetric;
      document.querySelectorAll('[data-state-metric]').forEach(item => item.classList.remove('active'));
      this.classList.add('active');
      renderStateBars();
    });
  });

  function donut(selector, labels, series, colors) {
    const el = document.querySelector(selector);
    if (!el || typeof ApexCharts === 'undefined') return;
    new ApexCharts(el, {
      series: series.length ? series : [0],
      chart: { type: 'donut', height: 170 },
      labels: labels.length ? labels : ['No data'],
      colors,
      dataLabels: { enabled: false },
      legend: { position: 'bottom', fontSize: '11px' },
      stroke: { width: 0 },
      plotOptions: { pie: { donut: { size: '66%' } } }
    }).render();
  }

  function bars(selector, labels, series, color) {
    const el = document.querySelector(selector);
    if (!el || typeof ApexCharts === 'undefined') return;
    new ApexCharts(el, {
      series: [{ data: series.length ? series : [0] }],
      chart: { type: 'bar', height: 190, toolbar: { show: false } },
      colors: [color],
      plotOptions: { bar: { horizontal: false, borderRadius: 6, columnWidth: '42%' } },
      dataLabels: { enabled: false },
      xaxis: { categories: labels.length ? labels : ['No data'] },
      grid: { borderColor: '#eef1e9', strokeDashArray: 3 }
    }).render();
  }

  donut('#genderChart', (data.gender || []).map(i => i.label), (data.gender || []).map(i => Number(i.total)), ['#2f5d3a', '#d6a32e', '#3a6e8f', '#c1452b']);
  bars('#ageChart', (data.ages || []).map(i => i.label), (data.ages || []).map(i => Number(i.total)), '#2f5d3a');
  donut('#channelChart', (data.channels || []).map(i => i.label), (data.channels || []).map(i => Number(i.total)), ['#2f5d3a', '#d6a32e', '#c1452b', '#3a6e8f']);

  renderStateBars();

  if (window.jQuery && jQuery.fn.DataTable) {
    const filters = Object.fromEntries(new URLSearchParams(window.location.search));
    const text = jQuery.fn.DataTable.render.text();
    const table = (selector, name, columns) => jQuery(selector).DataTable({
      processing: true,
      serverSide: true,
      pageLength: 10,
      lengthChange: false,
      searching: true,
      order: [],
      ajax: { url: window.location.pathname + '/table/' + encodeURIComponent(name), data: filters },
      columns,
      language: { emptyTable: 'No data found for this filter.', processing: 'Loading…' }
    });
    table('#userAnalyticsTable', 'users', [
      { data: 'customer', render: text }, { data: 'email', render: text },
      { data: 'orders' }, { data: 'paid_orders' },
      { data: 'revenue', render: value => 'Rs. ' + Number(value || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 }) },
      { data: 'last_order', render: text }
    ]);
    table('#shipmentAnalyticsTable', 'shipments', [
      { data: 'status', render: text }, { data: 'orders' },
      { data: 'shipping', render: value => 'Rs. ' + Number(value || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 }) },
      { data: 'revenue', render: value => 'Rs. ' + Number(value || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 }) }
    ]);
    table('#cohortRetentionTable', 'cohorts', [
      { data: 'cohort', render: text },
      ...[0, 1, 2, 3, 4].map(index => ({ data: null, render: row => row.months[index] === null ? '-' : row.months[index] + '%' }))
    ]);
  }
});
</script>
SCRIPT;
@endphp

@section('content')
    <style>
        .nb-analytics .card { border: 1px solid #eef1e9; box-shadow: 0 10px 28px rgba(18, 26, 21, .04); }
        .nb-analytics .nb-filter-bar { align-items: center; background: #fff; border: 1px solid #eef1e9; border-radius: 12px; display: flex; flex-wrap: wrap; gap: 12px; padding: 14px; }
        .nb-analytics .nb-date-input { min-width: 150px; }
        .nb-analytics .nb-zone-card { border: 1px solid #e7ecdf; border-radius: 12px; cursor: pointer; padding: 16px; transition: .18s ease; }
        .nb-analytics .nb-zone-card:hover, .nb-analytics .nb-zone-card.is-active { border-color: #2f5d3a; box-shadow: 0 12px 26px rgba(47, 93, 58, .08); transform: translateY(-2px); }
        .nb-analytics .nb-zone-name { color: #718076; font-size: 12px; font-weight: 800; text-transform: uppercase; }
        .nb-analytics .nb-zone-value { color: #111827; font-size: 20px; font-weight: 900; margin-top: 6px; }
        .nb-analytics .nb-zone-sub { color: #718076; font-size: 12px; margin-top: 2px; }
        .nb-analytics .nb-seg { background: #f6f8f3; border: 1px solid #e6ecdf; border-radius: 10px; display: inline-flex; gap: 4px; padding: 4px; }
        .nb-analytics .nb-seg button { background: transparent; border: 0; border-radius: 8px; color: #5f6a61; font-size: 12px; font-weight: 800; min-height: 34px; padding: 8px 12px; }
        .nb-analytics .nb-seg button.active { background: #fff; box-shadow: 0 5px 18px rgba(18, 26, 21, .08); color: #2f5d3a; }
        .nb-analytics .nb-state-bar { align-items: center; display: grid; gap: 12px; grid-template-columns: minmax(105px, 150px) 1fr minmax(90px, auto); padding: 9px 0; }
        .nb-analytics .nb-state-name { color: #27312b; font-size: 13px; font-weight: 700; }
        .nb-analytics .nb-state-track { background: #eef1e9; border-radius: 999px; height: 9px; overflow: hidden; }
        .nb-analytics .nb-state-track div { background: #2f5d3a; border-radius: inherit; height: 100%; }
        .nb-analytics .nb-state-value { font-size: 12px; font-weight: 800; text-align: right; }
        .nb-analytics .nb-city-bar { align-items: center; display: grid; gap: 10px; grid-template-columns: minmax(90px, 120px) 1fr minmax(80px, auto); padding: 8px 0; }
        .nb-analytics .nb-city-track { background: #eef1e9; border-radius: 999px; height: 8px; overflow: hidden; }
        .nb-analytics .nb-city-track div { background: #d6a32e; border-radius: inherit; height: 100%; }
        .nb-analytics .nb-kpi { border-left: 4px solid #2f5d3a; min-height: 118px; }
        .nb-analytics .nb-kpi-label { color: #718076; font-size: 12px; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; }
        .nb-analytics .nb-kpi-value { color: #111827; font-size: 22px; font-weight: 900; margin-top: 8px; }
        .nb-analytics .nb-kpi-note { color: #718076; font-size: 12px; margin-top: 4px; }
        .nb-analytics .nb-section-title { align-items: center; display: flex; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
        .nb-analytics .nb-table-wrap { max-height: 420px; overflow: auto; }
        .nb-analytics .nb-table-wrap th { background: #f8faf6; position: sticky; top: 0; z-index: 1; }
        @media (max-width: 575px) {
            .nb-analytics .nb-state-bar, .nb-analytics .nb-city-bar { grid-template-columns: 1fr; }
            .nb-analytics .nb-state-value { text-align: left; }
        }
    </style>

    <div class="nb-analytics">
        <form method="GET" action="{{ route('admin.analytics') }}" class="nb-filter-bar mb-24">
            <span class="text-sm fw-bold text-secondary-light">Range</span>
            <input type="date" name="start_date" class="form-control nb-date-input" value="{{ $filters['start_date'] }}">
            <span class="text-secondary-light">to</span>
            <input type="date" name="end_date" class="form-control nb-date-input" value="{{ $filters['end_date'] }}">

            <label class="text-sm fw-bold text-secondary-light ms-lg-2" for="product_id">Product</label>
            <select id="product_id" name="product_id" class="form-select w-auto">
                <option value="">All products</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected((int) $filters['product_id'] === $product->id)>{{ $product->name }}</option>
                @endforeach
            </select>

            <label class="text-sm fw-bold text-secondary-light ms-lg-2" for="segment">Segment</label>
            <select id="segment" name="segment" class="form-select w-auto">
                <option value="all" @selected($filters['segment'] === 'all')>All customers</option>
                <option value="returning" @selected($filters['segment'] === 'returning')>Returning</option>
                <option value="new" @selected($filters['segment'] === 'new')>New</option>
                <option value="subscribers" @selected($filters['segment'] === 'subscribers')>Subscribers</option>
            </select>

            <button type="submit" class="btn btn-primary-600 btn-sm ms-auto">Apply</button>
        </form>

        <div class="row gy-4">
            <div class="col-xxl-3 col-sm-6">
                <div class="card nb-kpi h-100">
                    <div class="card-body p-20">
                        <div class="nb-kpi-label">Sales</div>
                        <div class="nb-kpi-value">Rs. {{ number_format($summary['revenue'] ?? 0, 0) }}</div>
                        <div class="nb-kpi-note">{{ number_format($summary['paid_orders'] ?? 0) }} paid orders - AOV Rs. {{ number_format($summary['average_order_value'] ?? 0, 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-sm-6">
                <div class="card nb-kpi h-100">
                    <div class="card-body p-20">
                        <div class="nb-kpi-label">Orders</div>
                        <div class="nb-kpi-value">{{ number_format($summary['orders'] ?? 0) }}</div>
                        <div class="nb-kpi-note">All payment statuses in selected range</div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-sm-6">
                <div class="card nb-kpi h-100">
                    <div class="card-body p-20">
                        <div class="nb-kpi-label">Users</div>
                        <div class="nb-kpi-value">{{ number_format($summary['customers'] ?? 0) }}</div>
                        <div class="nb-kpi-note">{{ number_format($summary['guest_orders'] ?? 0) }} guest orders</div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-sm-6">
                <div class="card nb-kpi h-100">
                    <div class="card-body p-20">
                        <div class="nb-kpi-label">Returns</div>
                        <div class="nb-kpi-value">{{ number_format($summary['returns'] ?? 0) }}</div>
                        <div class="nb-kpi-note">{{ number_format($summary['return_rate'] ?? 0, 2) }}% rate - Rs. {{ number_format($summary['refunds'] ?? 0, 0) }} refund</div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-8">
                <div class="card h-100">
                    <div class="card-body p-24">
                        <div class="nb-section-title">
                            <div>
                                <h6 class="mb-2 fw-bold">Product Analytics</h6>
                                <p class="text-sm text-secondary-light mb-0">Top products by paid revenue, quantity and order count</p>
                            </div>
                        </div>
                        <div class="table-responsive nb-table-wrap">
                            <table class="table bordered-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Orders</th>
                                        <th>Qty</th>
                                        <th>Revenue</th>
                                        <th>AOV</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($productRows as $row)
                                        <tr>
                                            <td class="fw-bold">{{ $row['product'] }}</td>
                                            <td>{{ number_format($row['orders']) }}</td>
                                            <td>{{ number_format($row['quantity']) }}</td>
                                            <td>Rs. {{ number_format($row['revenue'], 0) }}</td>
                                            <td>Rs. {{ number_format($row['aov'], 0) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-secondary-light">No product sales found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-4">
                <div class="card h-100">
                    <div class="card-body p-24">
                        <h6 class="mb-18 fw-bold">Sales Summary</h6>
                        @foreach ([
                            'Gross paid revenue' => 'Rs. ' . number_format($summary['revenue'] ?? 0, 0),
                            'Average order value' => 'Rs. ' . number_format($summary['average_order_value'] ?? 0, 0),
                            'Discount given' => 'Rs. ' . number_format($summary['discount'] ?? 0, 0),
                            'Shipping charged' => 'Rs. ' . number_format($summary['shipping'] ?? 0, 0),
                            'Refund value' => 'Rs. ' . number_format($summary['refunds'] ?? 0, 0),
                        ] as $label => $value)
                            <div class="d-flex align-items-center justify-content-between py-10 border-bottom">
                                <span class="text-sm text-secondary-light">{{ $label }}</span>
                                <span class="fw-bold">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-xxl-6">
                <div class="card h-100">
                    <div class="card-body p-24">
                        <div class="nb-section-title">
                            <div>
                                <h6 class="mb-2 fw-bold">User Analytics</h6>
                                <p class="text-sm text-secondary-light mb-0">Customers ranked by revenue in this range</p>
                            </div>
                        </div>
                        <div class="table-responsive nb-table-wrap">
                            <table id="userAnalyticsTable" class="table bordered-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Email</th>
                                        <th>Orders</th>
                                        <th>Paid</th>
                                        <th>Revenue</th>
                                        <th>Last order</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-6">
                <div class="card h-100">
                    <div class="card-body p-24">
                        <div class="nb-section-title">
                            <div>
                                <h6 class="mb-2 fw-bold">Order Analytics</h6>
                                <p class="text-sm text-secondary-light mb-0">Order and payment status distribution</p>
                            </div>
                        </div>
                        <div class="row gy-4">
                            <div class="col-lg-6">
                                <p class="text-xs text-secondary-light fw-bold mb-8">ORDER STATUS</p>
                                @forelse ($orderStatusRows as $row)
                                    <div class="d-flex align-items-center justify-content-between py-8 border-bottom">
                                        <span class="text-sm fw-bold">{{ $row['status'] }}</span>
                                        <span class="text-sm">{{ number_format($row['orders']) }} - Rs. {{ number_format($row['revenue'], 0) }}</span>
                                    </div>
                                @empty
                                    <div class="text-sm text-secondary-light">No order data found.</div>
                                @endforelse
                            </div>
                            <div class="col-lg-6">
                                <p class="text-xs text-secondary-light fw-bold mb-8">PAYMENT STATUS</p>
                                @forelse ($paymentStatusRows as $row)
                                    <div class="d-flex align-items-center justify-content-between py-8 border-bottom">
                                        <span class="text-sm fw-bold">{{ $row['status'] }}</span>
                                        <span class="text-sm">{{ number_format($row['orders']) }} - Rs. {{ number_format($row['amount'], 0) }}</span>
                                    </div>
                                @empty
                                    <div class="text-sm text-secondary-light">No payment data found.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-6">
                <div class="card h-100">
                    <div class="card-body p-24">
                        <div class="nb-section-title">
                            <div>
                                <h6 class="mb-2 fw-bold">Shipment Analytics</h6>
                                <p class="text-sm text-secondary-light mb-0">Fulfillment or shipping status with charged shipping</p>
                            </div>
                        </div>
                        <div class="table-responsive nb-table-wrap">
                            <table id="shipmentAnalyticsTable" class="table bordered-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Orders</th>
                                        <th>Shipping</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-6">
                <div class="card h-100">
                    <div class="card-body p-24">
                        <div class="nb-section-title">
                            <div>
                                <h6 class="mb-2 fw-bold">Return Analytics</h6>
                                <p class="text-sm text-secondary-light mb-0">Return status and top reasons</p>
                            </div>
                        </div>
                        <div class="row gy-4">
                            <div class="col-lg-6">
                                <p class="text-xs text-secondary-light fw-bold mb-8">RETURN STATUS</p>
                                @forelse ($returnRows as $row)
                                    <div class="d-flex align-items-center justify-content-between py-8 border-bottom">
                                        <span class="text-sm fw-bold">{{ $row['status'] }}</span>
                                        <span class="text-sm">{{ number_format($row['returns']) }} - Rs. {{ number_format($row['refund'], 0) }}</span>
                                    </div>
                                @empty
                                    <div class="text-sm text-secondary-light">No returns found.</div>
                                @endforelse
                            </div>
                            <div class="col-lg-6">
                                <p class="text-xs text-secondary-light fw-bold mb-8">TOP REASONS</p>
                                @forelse ($returnReasonRows as $row)
                                    <div class="py-8 border-bottom">
                                        <div class="d-flex align-items-center justify-content-between gap-2">
                                            <span class="text-sm fw-bold">{{ $row['reason'] }}</span>
                                            <span class="text-sm">{{ number_format($row['returns']) }}</span>
                                        </div>
                                        <div class="text-xs text-secondary-light">Refund Rs. {{ number_format($row['refund'], 0) }}</div>
                                    </div>
                                @empty
                                    <div class="text-sm text-secondary-light">No return reasons found.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-8">
                <div class="card h-100">
                    <div class="card-body p-24">
                        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-20">
                            <div>
                                <h6 class="mb-2 fw-bold">India Sales by Zone</h6>
                                <p class="text-sm text-secondary-light mb-0">Tap a zone to drill into states</p>
                            </div>
                        </div>

                        <div class="row gy-3">
                            @foreach ($zones as $zone)
                                @php $key = $zone['key']; @endphp
                                <div class="col-md-6">
                                    <div class="nb-zone-card" data-zone="{{ $key }}">
                                        <div class="nb-zone-name">{{ $zone['zone'] }} Zone</div>
                                        <div class="nb-zone-value">Rs. {{ number_format($zone['total'], 0) }}</div>
                                        <div class="nb-zone-sub">{{ number_format($zone['percent'], 1) }}% share - {{ number_format($zone['orders']) }} orders</div>
                                        <div class="mt-8">
                                            <span class="badge bg-{{ $zoneClasses[$key] ?? 'secondary' }}-focus text-{{ $zoneClasses[$key] ?? 'secondary' }}-main">{{ $zone['states'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <hr class="my-24">

                        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-12">
                            <h6 class="mb-0 fw-bold" id="stateTitle">Top States - All Zones</h6>
                            <div class="nb-seg" id="stateMetric">
                                <button type="button" class="active" data-state-metric="rev">Revenue</button>
                                <button type="button" data-state-metric="ord">Orders</button>
                                <button type="button" data-state-metric="aov">AOV</button>
                            </div>
                        </div>
                        <div id="stateBars"></div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-4">
                <div class="card h-100">
                    <div class="card-body p-24">
                        <h6 class="mb-18 fw-bold">Customer Demographics</h6>

                        <p class="text-xs text-secondary-light fw-bold mb-8">GENDER SPLIT</p>
                        <div id="genderChart"></div>

                        <hr class="my-20">

                        <p class="text-xs text-secondary-light fw-bold mb-8">AGE BUCKETS</p>
                        <div id="ageChart"></div>

                        <hr class="my-20">

                        <p class="text-xs text-secondary-light fw-bold mb-8">TOP CITIES</p>
                        @php $maxCity = max(1, (float) collect($cities)->max('revenue')); @endphp
                        @forelse ($cities as $city)
                            <div class="nb-city-bar">
                                <span class="text-sm fw-bold">{{ $city['city'] }}</span>
                                <div class="nb-city-track"><div style="width: {{ max(4, ($city['revenue'] / $maxCity) * 100) }}%"></div></div>
                                <span class="text-xs fw-bold text-end">Rs. {{ number_format($city['revenue'], 0) }}</span>
                            </div>
                        @empty
                            <div class="text-sm text-secondary-light">No city data found.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-xxl-6">
                <div class="card h-100">
                    <div class="card-body p-24">
                        <div class="d-flex align-items-center justify-content-between mb-20">
                            <h6 class="mb-0 fw-bold">Channel Performance</h6>
                            <span class="text-sm text-secondary-light">Revenue split</span>
                        </div>
                        <div id="channelChart"></div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-6">
                <div class="card h-100">
                    <div class="card-body p-24">
                        <div class="d-flex align-items-center justify-content-between mb-20">
                            <h6 class="mb-0 fw-bold">Cohort Retention</h6>
                            <span class="text-sm text-secondary-light">Repeat purchase rate</span>
                        </div>
                        <div class="table-responsive">
                            <table id="cohortRetentionTable" class="table bordered-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Cohort</th>
                                        <th>M1</th>
                                        <th>M2</th>
                                        <th>M3</th>
                                        <th>M4</th>
                                        <th>M5</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
