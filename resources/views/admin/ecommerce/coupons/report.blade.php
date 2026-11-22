@extends('layouts.admin')
@php
    $title = 'Coupon Report';
    $subTitle = 'Ecommerce / Coupons';
    $money = fn ($amount) => 'INR ' . number_format((float) $amount, 2);
@endphp

@section('content')
    @include('admin.partials.messages')

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    {{-- Filter --}}
    <div class="card border-0 radius-12 mb-24">
        <div class="card-body p-24">
            <form method="GET" action="{{ route('admin.ecommerce.coupons.report') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">From</label>
                    <input type="date" name="from" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary-600">Apply</button>
                    <a href="{{ route('admin.ecommerce.coupons.report') }}" class="btn btn-outline-secondary">Reset</a>
                    <a href="{{ route('admin.ecommerce.coupons.index') }}" class="btn btn-outline-primary-600">Back to coupons</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Totals --}}
    <div class="row g-3 mb-24">
        <div class="col-md-4">
            <div class="card border-0 radius-12 h-100"><div class="card-body p-20">
                <span class="text-sm text-secondary-light d-block">Total discount given</span>
                <h5 class="mb-0">{{ $money($totals->total_discount ?? 0) }}</h5>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 radius-12 h-100"><div class="card-body p-20">
                <span class="text-sm text-secondary-light d-block">Orders using a coupon</span>
                <h5 class="mb-0">{{ number_format((int) ($totals->orders_count ?? 0)) }}</h5>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 radius-12 h-100"><div class="card-body p-20">
                <span class="text-sm text-secondary-light d-block">Customers who used a coupon</span>
                <h5 class="mb-0">{{ number_format((int) ($totals->customers_count ?? 0)) }}</h5>
            </div></div>
        </div>
    </div>

    {{-- Per coupon --}}
    <div class="card basic-data-table">
        <div class="card-header">
            <h5 class="card-title mb-0">Usage by coupon</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>
                    <thead>
                        <tr>
                            <th>Coupon</th>
                            <th>Times used (orders)</th>
                            <th>Customers</th>
                            <th>Total discount given</th>
                            <th>Avg discount / order</th>
                            <th>Discount % of order value</th>
                            <th>Limit used</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report as $row)
                            @php $coupon = $row['coupon']; @endphp
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-primary-600">{{ $coupon->code }}</span>
                                        <small class="text-secondary-light">{{ $coupon->name ?? 'No Name' }}</small>
                                    </div>
                                </td>
                                <td data-order="{{ $row['orders'] }}">{{ number_format($row['orders']) }}</td>
                                <td data-order="{{ $row['customers'] }}">{{ number_format($row['customers']) }}</td>
                                <td data-order="{{ $row['total_discount'] }}" class="fw-semibold">{{ $money($row['total_discount']) }}</td>
                                <td data-order="{{ $row['average_discount'] }}">{{ $money($row['average_discount']) }}</td>
                                <td data-order="{{ $row['discount_rate'] }}">{{ number_format($row['discount_rate'], 1) }}%</td>
                                <td data-order="{{ $row['limit_used'] ?? -1 }}">
                                    @if ($row['limit_used'] !== null)
                                        {{ number_format($row['limit_used'], 0) }}% ({{ $coupon->used_count }}/{{ $coupon->usage_limit }})
                                    @else
                                        <span class="text-secondary-light">No limit</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($coupon->is_active)
                                        <span class="badge bg-success-100 text-success-600 px-2 fw-medium">Active</span>
                                    @else
                                        <span class="badge bg-danger-100 text-danger-600 px-2 fw-medium">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-secondary-light mt-3 mb-0">
                Cancelled orders are not counted. Discount is the coupon discount only (NB Coin discounts are not included).
                The date filter uses the order date; "Limit used" always reflects all-time usage.
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('dataTable')) {
                new DataTable('#dataTable', { order: [[3, 'desc']] });
            }
        });
    </script>
@endsection