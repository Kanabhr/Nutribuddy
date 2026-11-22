@extends('layouts.admin')
@php
    $title = 'Customer Profile';
    $subTitle = 'Ecommerce / Customers';
    $adminUser = auth('admin')->user();
    $canViewOrders = $adminUser?->canAny(['view orders', 'manage orders']) ?? false;
    $statusClass = fn ($status) => match (strtolower((string) $status)) {
        'completed', 'delivered' => 'success',
        'shipped', 'processing' => 'info',
        'cancelled' => 'danger',
        default => 'warning',
    };
    $money = fn ($amount) => 'INR ' . number_format((float) $amount, 2);
@endphp

@section('content')
    @include('admin.partials.messages')

    {{-- Header --}}
    <div class="card border-0 radius-12 mb-24">
        <div class="card-body p-24 d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h4 class="mb-1">{{ $customer->name }}</h4>
                <div class="d-flex flex-wrap gap-3 text-sm text-secondary-light">
                    <span>ID: #{{ $customer->id }}</span>
                    <span>{{ $customer->email }}</span>
                    <span>{{ $customer->phone ?: 'No phone' }}</span>
                    <span>Joined {{ optional($customer->created_at)->format('d M Y') ?? 'N/A' }}</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if ($customer->is_active)
                    <span class="badge bg-success-100 text-success-600 px-2 fw-medium">Active</span>
                @else
                    <span class="badge bg-danger-100 text-danger-600 px-2 fw-medium">Inactive</span>
                @endif
                <a href="{{ route('admin.ecommerce.customers.index') }}" class="btn btn-sm btn-outline-secondary radius-8">Back to customers</a>
            </div>
        </div>
    </div>

    {{-- Key numbers --}}
    <div class="row g-3 mb-24">
        <div class="col-6 col-md-3">
            <div class="card border-0 radius-12 h-100"><div class="card-body p-20">
                <span class="text-sm text-secondary-light d-block">Lifetime spend</span>
                <h5 class="mb-0">{{ $money($stats['lifetime_spend']) }}</h5>
                <small class="text-secondary-light">Paid, non-cancelled orders</small>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 radius-12 h-100"><div class="card-body p-20">
                <span class="text-sm text-secondary-light d-block">Orders placed</span>
                <h5 class="mb-0">{{ number_format($stats['orders_placed']) }}</h5>
                <small class="text-secondary-light">{{ $stats['orders_cancelled'] }} cancelled, {{ $stats['returns'] }} returns</small>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 radius-12 h-100"><div class="card-body p-20">
                <span class="text-sm text-secondary-light d-block">Average order</span>
                <h5 class="mb-0">{{ $money($stats['average_order']) }}</h5>
                <small class="text-secondary-light">
                    @if ($stats['last_order_at'])
                        Last order {{ $stats['last_order_at']->format('d M Y') }}
                    @else
                        No orders yet
                    @endif
                </small>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 radius-12 h-100"><div class="card-body p-20">
                <span class="text-sm text-secondary-light d-block">NB Coins balance</span>
                <h5 class="mb-0">{{ number_format((int) $customer->coins_balance) }}</h5>
                <small class="text-secondary-light">{{ number_format($stats['coins_earned']) }} earned in total</small>
            </div></div>
        </div>
    </div>

    <div class="row g-3 mb-24">
        {{-- Addresses --}}
        <div class="col-lg-6">
            <div class="card border-0 radius-12 h-100">
                <div class="card-header bg-base border-bottom py-16 px-24"><h6 class="mb-0">Saved addresses</h6></div>
                <div class="card-body p-24">
                    @forelse ($customer->customerAddresses as $address)
                        <div class="{{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-semibold text-dark">{{ $address->full_name ?: $customer->name }}</span>
                                @if ($address->label)<span class="badge bg-secondary-100 text-secondary-600 text-xs">{{ $address->label }}</span>@endif
                                @if ($address->is_default_shipping)<span class="badge bg-info-100 text-info-600 text-xs">Default shipping</span>@endif
                                @if ($address->is_default_billing)<span class="badge bg-success-100 text-success-600 text-xs">Default billing</span>@endif
                            </div>
                            <div class="text-sm text-secondary-light">
                                {{ collect([$address->address_line_1, $address->address_line_2, $address->landmark])->filter()->implode(', ') }}<br>
                                {{ collect([$address->city, $address->state, $address->postal_code])->filter()->implode(', ') }}
                                @if ($address->phone)<br>{{ $address->phone }}@endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-secondary-light mb-0">No saved addresses.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Addresses used on orders --}}
        <div class="col-lg-6">
            <div class="card border-0 radius-12 h-100">
                <div class="card-header bg-base border-bottom py-16 px-24"><h6 class="mb-0">Shipping addresses used on orders</h6></div>
                <div class="card-body p-24">
                    @forelse ($orderAddresses as $address)
                        <div class="{{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                            <span class="fw-semibold text-dark d-block">{{ $address->shipping_name }}</span>
                            <div class="text-sm text-secondary-light">
                                {{ collect([$address->shipping_address_line_1, $address->shipping_address_line_2])->filter()->implode(', ') }}<br>
                                {{ collect([$address->shipping_city, $address->shipping_state, $address->shipping_postal_code])->filter()->implode(', ') }}
                                @if ($address->shipping_phone)<br>{{ $address->shipping_phone }}@endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-secondary-light mb-0">No order addresses yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Order history --}}
    <div class="card border-0 radius-12 mb-24">
        <div class="card-header bg-base border-bottom py-16 px-24 d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Order history</h6>
            @if ($stats['orders_placed'] > $orders->count())
                <small class="text-secondary-light">Showing latest {{ $orders->count() }} of {{ $stats['orders_placed'] }}</small>
            @endif
        </div>
        <div class="card-body p-24">
            <div class="table-responsive">
                <table class="table bordered-table mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Items</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            @php $class = $statusClass($order->status); @endphp
                            <tr>
                                <td>
                                    @if ($canViewOrders)
                                        <a href="{{ route('admin.ecommerce.orders.show', $order) }}" class="fw-bold text-primary-600">#{{ $order->order_number }}</a>
                                    @else
                                        <span class="fw-bold">#{{ $order->order_number }}</span>
                                    @endif
                                </td>
                                <td class="text-sm text-secondary-light">{{ optional($order->placed_at ?? $order->created_at)->format('d M Y, H:i') }}</td>
                                <td><span class="badge bg-{{ $class }}-100 text-{{ $class }}-600">{{ ucfirst($order->status) }}</span></td>
                                <td class="text-sm">{{ ucfirst($order->payment_status) }} ({{ strtoupper($order->payment_method) }})</td>
                                <td class="text-sm">{{ $order->items_count }}</td>
                                <td class="text-end fw-semibold">{{ $money($order->grand_total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-secondary-light py-4">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-24">
        {{-- Coins --}}
        <div class="col-lg-6">
            <div class="card border-0 radius-12 h-100">
                <div class="card-header bg-base border-bottom py-16 px-24"><h6 class="mb-0">NB Coins activity</h6></div>
                <div class="card-body p-24">
                    <div class="table-responsive">
                        <table class="table bordered-table mb-0">
                            <thead>
                                <tr><th>Date</th><th>Type</th><th>Coins</th><th>Note</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($coinTransactions as $transaction)
                                    <tr>
                                        <td class="text-sm text-secondary-light">{{ optional($transaction->created_at)->format('d M Y') }}</td>
                                        <td>
                                            <span class="badge {{ $transaction->type === 'earned' ? 'bg-success-100 text-success-600' : 'bg-warning-100 text-warning-600' }}">{{ ucfirst($transaction->type) }}</span>
                                        </td>
                                        <td class="fw-semibold">{{ $transaction->type === 'earned' ? '+' : '' }}{{ number_format((int) $transaction->amount) }}</td>
                                        <td class="text-sm text-secondary-light">
                                            {{ $transaction->description }}
                                            @if ($transaction->order)
                                                <span class="d-block">Order #{{ $transaction->order->order_number }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-secondary-light py-4">No coin activity yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Subscriptions --}}
        <div class="col-lg-6">
            <div class="card border-0 radius-12 h-100">
                <div class="card-header bg-base border-bottom py-16 px-24"><h6 class="mb-0">Diet plan subscriptions</h6></div>
                <div class="card-body p-24">
                    <div class="table-responsive">
                        <table class="table bordered-table mb-0">
                            <thead>
                                <tr><th>Plan</th><th>Status</th><th>Period</th><th class="text-end">Paid</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($subscriptions as $subscription)
                                    <tr>
                                        <td class="fw-medium">{{ $subscription->subscriptionPlan->name ?? 'Plan removed' }}</td>
                                        <td>
                                            @if ($subscription->is_currently_active)
                                                <span class="badge bg-success-100 text-success-600">Active</span>
                                            @else
                                                <span class="badge bg-secondary-100 text-secondary-600">{{ ucfirst($subscription->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-sm text-secondary-light">
                                            {{ optional($subscription->starts_at)->format('d M Y') ?? 'N/A' }}
                                            to
                                            {{ optional($subscription->expires_at)->format('d M Y') ?? 'no expiry' }}
                                        </td>
                                        <td class="text-end">{{ $money($subscription->amount_paid) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-secondary-light py-4">No subscriptions.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Reviews --}}
    <div class="card border-0 radius-12 mb-24">
        <div class="card-header bg-base border-bottom py-16 px-24 d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Reviews</h6>
            <small class="text-secondary-light">{{ $stats['reviews'] }} total, {{ $stats['support_tickets'] }} support tickets</small>
        </div>
        <div class="card-body p-24">
            @forelse ($reviews as $review)
                <div class="{{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="fw-semibold text-dark">{{ $review->product->name ?? 'Product removed' }}</span>
                        <span class="text-warning-main">{{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', max(0, 5 - (int) $review->rating)) }}</span>
                        <span class="badge {{ $review->is_active ? 'bg-success-100 text-success-600' : 'bg-danger-100 text-danger-600' }}">{{ $review->is_active ? 'Published' : 'Hidden' }}</span>
                        <small class="text-secondary-light">{{ optional($review->created_at)->format('d M Y') }}</small>
                    </div>
                    <p class="text-sm text-secondary-light mb-0">{{ $review->comment ?: 'No comment' }}</p>
                </div>
            @empty
                <p class="text-sm text-secondary-light mb-0">No reviews yet.</p>
            @endforelse
        </div>
    </div>
@endsection