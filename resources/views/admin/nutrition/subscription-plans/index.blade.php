@extends('layouts.admin')
@php
    $title = 'Diet Plans';
    $subTitle = 'Diet Services Management';
    $script = <<<'SCRIPT'
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartData = window.planPurchasesData || {};
    const labels = Object.keys(chartData);
    const data = Object.values(chartData);

    const el = document.querySelector('#dietPlansChart');
    if (el && typeof ApexCharts !== 'undefined') {
        new ApexCharts(el, {
            series: [{ name: 'Purchases', data: data }],
            chart: { type: 'bar', height: 350, toolbar: { show: false } },
            plotOptions: { bar: { borderRadius: 4, horizontal: false, columnWidth: '40%' } },
            dataLabels: { enabled: false },
            xaxis: { categories: labels },
            colors: ['#2f5d3a'],
            tooltip: { y: { formatter: function (val) { return val + " purchases" } } }
        }).render();
    }
});
</script>
SCRIPT;
@endphp

@section('content')
    @include('admin.partials.messages')

    <div class="row gy-4 mb-24">
        <!-- Total Plans -->
        <div class="col-xxl-3 col-sm-6">
            <div class="card p-3 p-xl-4 h-100 bg-base border-0 radius-12 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0 fw-semibold text-secondary-light">Total Diet Plans</h6>
                    <div class="w-40-px h-40-px rounded-circle bg-primary-50 text-primary-600 d-flex align-items-center justify-content-center text-xl">
                        <iconify-icon icon="lucide:clipboard-list"></iconify-icon>
                    </div>
                </div>
                <h4 class="mb-0 fw-bold">{{ $totalPlans }}</h4>
            </div>
        </div>

        <!-- Total Purchases -->
        <div class="col-xxl-3 col-sm-6">
            <div class="card p-3 p-xl-4 h-100 bg-base border-0 radius-12 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0 fw-semibold text-secondary-light">Total Purchases</h6>
                    <div class="w-40-px h-40-px rounded-circle bg-success-50 text-success-600 d-flex align-items-center justify-content-center text-xl">
                        <iconify-icon icon="lucide:shopping-bag"></iconify-icon>
                    </div>
                </div>
                <h4 class="mb-0 fw-bold">{{ $totalPurchases }}</h4>
            </div>
        </div>

        <!-- Active Subscriptions -->
        <div class="col-xxl-3 col-sm-6">
            <div class="card p-3 p-xl-4 h-100 bg-base border-0 radius-12 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0 fw-semibold text-secondary-light">Active Subscriptions</h6>
                    <div class="w-40-px h-40-px rounded-circle bg-info-50 text-info-600 d-flex align-items-center justify-content-center text-xl">
                        <iconify-icon icon="lucide:activity"></iconify-icon>
                    </div>
                </div>
                <h4 class="mb-0 fw-bold">{{ $activeSubscriptions }}</h4>
            </div>
        </div>

        <!-- Total Revenue -->
        <div class="col-xxl-3 col-sm-6">
            <div class="card p-3 p-xl-4 h-100 bg-base border-0 radius-12 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0 fw-semibold text-secondary-light">Total Revenue</h6>
                    <div class="w-40-px h-40-px rounded-circle bg-warning-50 text-warning-600 d-flex align-items-center justify-content-center text-xl">
                        <iconify-icon icon="lucide:indian-rupee"></iconify-icon>
                    </div>
                </div>
                <h4 class="mb-0 fw-bold">₹{{ number_format($totalRevenue, 2) }}</h4>
            </div>
        </div>
    </div>

    <!-- Chart Row -->
    <div class="row mb-24">
        <div class="col-12">
            <div class="card bg-base border-0 radius-12 shadow-sm">
                <div class="card-header bg-base border-bottom py-16 px-24">
                    <h5 class="card-title mb-0">Purchases per Diet Plan</h5>
                </div>
                <div class="card-body p-24">
                    <div id="dietPlansChart"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.planPurchasesData = @json($planPurchasesData);
    </script>

    <div class="card basic-data-table border-0 radius-12 mb-24">
        <div class="card-header bg-base border-bottom py-16 px-24">
            <h5 class="card-title mb-0">Diet Plans List</h5>
        </div>
        <div class="card-body p-24">
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Base Price</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($plans as $plan)
                            <tr>
                                <td><span class="text-sm text-secondary-light">{{ $plan->id }}</span></td>
                                <td><span class="text-md fw-semibold text-dark">{{ $plan->name }}</span></td>
                                <td><span class="text-md text-dark">₹{{ number_format($plan->price, 2) }}</span></td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @if($plan->is_active)
                                            <span class="badge bg-success-100 text-success-600 w-fit">Active</span>
                                        @else
                                            <span class="badge bg-danger-100 text-danger-600 w-fit">Inactive</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <a href="{{ route('admin.ecommerce.diet-plans.edit', $plan) }}"
                                            class="btn btn-sm btn-outline-success-600 radius-8 d-inline-flex align-items-center gap-1">
                                            <iconify-icon icon="lucide:edit"></iconify-icon> Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
