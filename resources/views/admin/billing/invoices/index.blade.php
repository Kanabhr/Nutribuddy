@extends('layouts.admin')
@php
    $title = 'Billing & Invoices';
    $subTitle = 'Ecommerce / Invoices';
@endphp

@section('content')
    <div class="card basic-data-table">
        <div class="card-header d-flex align-items-center justify-content-between gap-3">
            <h5 class="card-title mb-0">Invoice List</h5>
            <form action="{{ route('admin.ecommerce.invoices.index') }}" method="GET" class="d-flex gap-2 flex-wrap justify-content-end">
                <input type="text" name="search" class="form-control form-control-sm w-auto" placeholder="Order #, Name..." value="{{ request('search') }}">
                <select name="product_id" class="form-select form-select-sm w-auto"><option value="">All products / plans</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>{{ $product->name }}</option>@endforeach</select>
                <select name="payment_status" class="form-select form-select-sm w-auto"><option value="">All payments</option>@foreach(['paid', 'pending', 'failed', 'refunded'] as $status)<option value="{{ $status }}" @selected(request('payment_status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
                <button type="submit" class="btn btn-sm btn-primary">Search</button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table bordered-table mb-0">
                    <thead>
                        <tr>
                            <th>Invoice ID</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Order ID</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.ecommerce.invoices.show', $order) }}" class="text-primary-600 fw-bold">#INV-{{ $order->order_number }}</a>
                                    <a href="{{ route('admin.ecommerce.invoices.show', $order) }}" class="text-primary-600 fw-bold">{{ $order->invoice_number ?: ('#INV-' . $order->order_number) }}</a>
                                </td>
                                <td>{{ optional($order->placed_at)->format('d M Y') ?? $order->created_at->format('d M Y') }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-md mb-0 fw-medium">{{ $order->customer_name }}</h6>
                                            <span class="text-sm text-secondary-light">{{ $order->customer_email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td><a href="{{ route('admin.ecommerce.orders.show', $order) }}" class="text-primary-600 fw-bold">#{{ $order->order_number }}</a></td>
                                <td>₹{{ number_format($order->grand_total, 2) }}</td>
                                <td>
                                    <span class="badge {{ $order->payment_status === 'paid' ? 'bg-success-100 text-success-600' : 'bg-warning-100 text-warning-600' }}">
                                        {{ strtoupper($order->payment_status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="{{ route('admin.ecommerce.invoices.show', $order) }}" class="w-32-px h-32-px bg-primary-light text-primary-600 rounded-circle d-flex align-items-center justify-content-center" title="View Details">
                                            <iconify-icon icon="lucide:eye"></iconify-icon>
                                        </a>
                                        <a href="{{ route('admin.ecommerce.orders.invoice-download', $order) }}?print=1" target="_blank" class="w-32-px h-32-px bg-success-light text-success-600 rounded-circle d-flex align-items-center justify-content-center" title="Print Invoice">
                                            <iconify-icon icon="lucide:printer"></iconify-icon>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">No invoices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-24">
                <p class="text-secondary-light">Showing {{ $orders->firstItem() ?? 0 }} to {{ $orders->lastItem() ?? 0 }} of {{ $orders->total() }} entries</p>
                {{ $orders->links() }}
            </div>
        </div>
    </div>
@endsection
