@extends('layouts.admin')

@php
    $title = 'Diet Subscriptions';
    $subTitle = 'Diet Services Management / Subscriptions';
    $script = <<<'SCRIPT'
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !jQuery.fn.DataTable) return;

    const $table = jQuery('#dietSubscriptionsTable');
    const escapeText = value => {
        const element = document.createElement('span');
        element.textContent = String(value ?? '');
        return element.innerHTML;
    };
    const statusClasses = {
        active: 'bg-success-focus text-success-main',
        expired: 'bg-danger-focus text-danger-main',
        pending: 'bg-warning-focus text-warning-main',
        cancelled: 'bg-secondary-focus text-secondary-main',
        suspended: 'bg-secondary-focus text-secondary-main',
        paid: 'bg-success-focus text-success-main',
        failed: 'bg-danger-focus text-danger-main'
    };
    const badge = function (value) {
        const label = String(value || 'unknown');
        const displayLabel = label.charAt(0).toUpperCase() + label.slice(1);
        const safeLabel = escapeText(displayLabel);
        const classes = statusClasses[label.toLowerCase()] || 'bg-secondary-focus text-secondary-main';
        return '<span class="' + classes + ' px-16 py-4 rounded-pill fw-medium text-sm">' + safeLabel + '</span>';
    };

    const dataTable = $table.DataTable({
        processing: true,
        serverSide: true,
        pageLength: 10,
        lengthChange: false,
        searchDelay: 350,
        order: [[6, 'desc']],
        ajax: {
            url: $table.data('source'),
            data: function (request) {
                request.status = jQuery('#filterStatus').val();
                request.payment_status = jQuery('#filterPaymentStatus').val();
                request.plan_id = jQuery('#filterPlan').val();
                request.start_date = jQuery('#filterStartDate').val();
                request.end_date = jQuery('#filterEndDate').val();
            }
        },
        columns: [
            { data: 'user', render: escapeText },
            { data: 'email', render: escapeText },
            { data: 'plan', render: escapeText },
            { data: 'amount_paid', render: value => '₹' + Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
            { data: 'status', render: badge },
            { data: 'payment_status', render: badge },
            { data: 'starts_at', render: escapeText },
            { data: 'expires_at', render: escapeText },
            {
                data: 'id',
                orderable: false,
                searchable: false,
                render: id => '<a href="' + window.location.pathname + '/' + encodeURIComponent(id) + '" class="w-32-px h-32-px bg-success-focus text-success-main rounded-circle d-inline-flex align-items-center justify-content-center" aria-label="View subscription"><iconify-icon icon="lucide:eye"></iconify-icon></a>'
            }
        ],
        language: { emptyTable: 'No subscriptions match these filters.', processing: 'Loading subscriptions…' }
    });

    jQuery('#subscriptionFilters').on('submit', function (event) { event.preventDefault(); });
    jQuery('#subscriptionFilters').on('change', 'select, input[type="date"]', function () {
        dataTable.ajax.reload();
    });
    jQuery('#resetSubscriptionFilters').on('click', function () {
        jQuery('#subscriptionFilters')[0].reset();
        dataTable.search('');
        dataTable.ajax.reload();
    });
});
</script>
SCRIPT;
@endphp

@section('content')
<div class="card basic-data-table">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">Diet Subscriptions</h5>
    </div>
    <div class="card-body">
        <form id="subscriptionFilters" class="row g-3 align-items-end mb-20">
            <div class="col-xl-2 col-md-4">
                <label for="filterStatus" class="form-label">Subscription status</label>
                <select id="filterStatus" class="form-select">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-2 col-md-4">
                <label for="filterPaymentStatus" class="form-label">Payment status</label>
                <select id="filterPaymentStatus" class="form-select">
                    <option value="">All payment statuses</option>
                    @foreach ($paymentStatuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-2 col-md-4">
                <label for="filterPlan" class="form-label">Plan</label>
                <select id="filterPlan" class="form-select">
                    <option value="">All plans</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-2 col-md-4">
                <label for="filterStartDate" class="form-label">Created from</label>
                <input type="date" id="filterStartDate" class="form-control">
            </div>
            <div class="col-xl-2 col-md-4">
                <label for="filterEndDate" class="form-label">Created to</label>
                <input type="date" id="filterEndDate" class="form-control">
            </div>
            <div class="col-xl-2 col-md-4">
                <button type="button" id="resetSubscriptionFilters" class="btn btn-outline-secondary w-100">Clear filters</button>
            </div>
        </form>

        <div class="table-responsive">
            <table id="dietSubscriptionsTable" data-source="{{ route('admin.nutrition.diet-subscriptions.data') }}" class="table bordered-table mb-0 w-100">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Plan</th>
                        <th>Amount Paid</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection
