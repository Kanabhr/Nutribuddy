@extends('layouts.admin')
@php
    $title = 'Order Details';
    $subTitle = 'Ecommerce / Order #' . $order->order_number;
    $coinsEarned = (int) data_get($order->pricing_snapshot, 'coins_earned', 0);
    $grossTotal = (float) $order->subtotal + (float) $order->tax_total + (float) $order->shipping_total;
    $totalDiscount = (float) $order->discount_total + (float) $order->coin_discount;
@endphp

@section('content')
    <style>
        .order-quick-card {
            border: 1px solid #eef2f7;
            border-radius: 8px;
            padding: 14px 16px;
            height: 100%;
            background: #fff;
        }

        .order-quick-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .02em;
            margin-bottom: 6px;
        }

        .order-quick-value {
            color: #111827;
            font-size: 18px;
            font-weight: 800;
        }

        .amount-flow {
            width: 100%;
        }

        .amount-flow-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            padding: 10px 0;
            border-bottom: 1px solid #eef2f7;
            min-width: 0;
        }

        .amount-flow-row>div {
            min-width: 0;
        }

        .amount-flow-row>strong,
        .amount-flow-row>h5 {
            flex-shrink: 0;
            text-align: right;
            white-space: nowrap;
        }

        .amount-flow-row:last-child {
            border-bottom: 0;
        }

        .amount-flow-label {
            color: #4b5563;
            font-weight: 700;
        }

        .amount-flow-note {
            display: block;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 600;
            margin-top: 2px;
            overflow-wrap: anywhere;
        }

        @media (max-width: 575.98px) {
            .amount-flow-row {
                flex-direction: column;
                gap: 4px;
            }

            .amount-flow-row>strong,
            .amount-flow-row>h5 {
                text-align: left;
                white-space: normal;
            }
        }
    </style>

    @include('admin.partials.messages')

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Update Order Status</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.ecommerce.orders.update-status', $order) }}">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label class="form-label">Order Status</label>
                            <select name="status" class="form-select" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" {{ $order->status === $status ? 'selected' : '' }}>
                                        {{ ucfirst($status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Status</label>
                            <select name="payment_status" class="form-select" required>
                                @foreach ($paymentStatuses as $paymentStatus)
                                    <option value="{{ $paymentStatus }}"
                                        {{ $order->payment_status === $paymentStatus ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $paymentStatus)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Admin Note</label>
                            <textarea name="admin_note" rows="4" class="form-control">{{ $order->admin_note }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Status</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8 d-flex flex-column gap-4">
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <div class="order-quick-card">
                        <div class="order-quick-label">Collect</div>
                        <div class="order-quick-value text-primary-600">INR
                            {{ number_format((float) $order->grand_total, 2) }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="order-quick-card">
                        <div class="order-quick-label">Order Status</div>
                        <div class="order-quick-value">{{ ucfirst($order->status) }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="order-quick-card">
                        <div class="order-quick-label">Payment</div>
                        <div class="order-quick-value">{{ ucfirst($order->payment_status) }}</div>
                        <span class="amount-flow-note">{{ strtoupper($order->payment_method) }}</span>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="order-quick-card">
                        <div class="order-quick-label">Coins</div>
                        <div class="order-quick-value">{{ number_format((int) $order->coins_redeemed) }}</div>
                        <span class="amount-flow-note">INR {{ number_format((float) $order->coin_discount, 2) }}
                            redeemed</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Order #:</strong> {{ $order->order_number }}</div>
                        <div class="col-md-6"><strong>Placed At:</strong>
                            {{ optional($order->placed_at)->format('d M Y H:i') ?? (optional($order->created_at)->format('d M Y H:i') ?? 'N/A') }}
                        </div>
                        <div class="col-md-6"><strong>Customer:</strong> {{ $order->customer_name }}
                            ({{ $order->customer_phone }})</div>
                        <div class="col-md-6"><strong>Email:</strong> {{ $order->customer_email ?: 'N/A' }}</div>
                        <div class="col-md-6"><strong>Payment:</strong> {{ strtoupper($order->payment_method) }} /
                            {{ ucfirst($order->payment_status) }}</div>
                        <div class="col-md-6"><strong>Status:</strong> {{ ucfirst($order->status) }}</div>

                        <div class="col-md-6"><strong>Coupon:</strong> {{ $order->coupon_code ?: 'N/A' }}</div>
                        <div class="col-md-6">
                            <strong>NB Coins Used:</strong>
                            @if ((int) $order->coins_redeemed > 0 || (float) $order->coin_discount > 0)
                                {{ number_format((int) $order->coins_redeemed) }} coins
                                <span class="text-secondary-light">(INR
                                    {{ number_format((float) $order->coin_discount, 2) }} redeemed)</span>
                            @else
                                N/A
                            @endif
                        </div>
                        <div class="col-md-6"><strong>NB Coins Earned:</strong> {{ number_format($coinsEarned) }} coins
                        </div>
                        <div class="col-12">
                            <strong>Shipping Address:</strong>
                            <div>
                                {{ $order->shipping_name }}, {{ $order->shipping_phone }}<br>
                                {{ $order->shipping_address_line_1 }} {{ $order->shipping_address_line_2 }}<br>
                                {{ $order->shipping_city }}, {{ $order->shipping_state }} -
                                {{ $order->shipping_postal_code }}, {{ $order->shipping_country }}
                            </div>
                        </div>
                        @if ($order->customer_note)
                            <div class="col-12"><strong>Customer Note:</strong> {{ $order->customer_note }}</div>
                        @endif
                        <div class="col-12 mt-3 pt-3 border-top d-flex gap-2">
                            <a href="{{ route('admin.ecommerce.invoices.show', $order) }}"
                                class="btn btn-sm btn-info text-white d-inline-flex align-items-center gap-1">
                                <iconify-icon icon="lucide:file-text"></iconify-icon> View Official Invoice
                            </a>
                            <a href="{{ route('admin.ecommerce.orders.invoice-download', $order) }}?print=1"
                                target="_blank"
                                class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                                <iconify-icon icon="lucide:printer"></iconify-icon> Print for Packaging
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            @if ($order->returns()->exists())
                <div class="card border border-warning shadow-none">
                    <div class="card-header bg-warning-focus d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 text-warning-main">Return Information ↩️</h5>
                        <a href="{{ route('admin.ecommerce.order-returns.show', $order->returns()->latest()->first()) }}"
                            class="btn btn-sm btn-warning-main">View Full Return Details</a>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4"><strong>Return #:</strong> <a
                                    href="{{ route('admin.ecommerce.order-returns.show', $order->returns()->latest()->first()) }}"
                                    class="text-primary-600 fw-bold">#{{ $order->returns()->latest()->first()->return_number }}</a>
                            </div>
                            <div class="col-md-4"><strong>Status:</strong> <span
                                    class="badge bg-warning-focus text-warning-main">{{ strtoupper($order->returns()->latest()->first()->status) }}</span>
                            </div>
                            <div class="col-md-4"><strong>Requested At:</strong>
                                {{ $order->returns()->latest()->first()->created_at->format('d M Y') }}</div>
                            <div class="col-12"><strong>Reason:</strong> {{ $order->returns()->latest()->first()->reason }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table bordered-table mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Tax</th>
                                    <th>Discount</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="text-md fw-medium">{{ $item->product_name }}</span>
                                                @php
                                                    $vName =
                                                        $item->item_snapshot['variant_name'] ??
                                                        ($item->productVariant?->name ?? null);
                                                    $variant = $item->productVariant;
                                                    $product = $item->product;
                                                @endphp
                                                @if ($vName)
                                                    <small class="text-secondary-light d-block">{{ $vName }}</small>
                                                @endif
                                                <div class="d-flex flex-wrap gap-2 mt-1">
                                                    @php
                                                        $specs = collect();
                                                        if ($product) {
                                                            if ($product->flavor || $product->flavour) {
                                                                $specs->put('flavor', [
                                                                    'k' => 'Flavor',
                                                                    'v' => $product->flavor ?? $product->flavour,
                                                                    'c' => 'success',
                                                                ]);
                                                            }
                                                            if ($product->pack_size) {
                                                                $specs->put('pack', [
                                                                    'k' => 'Pack Size',
                                                                    'v' => $product->pack_size,
                                                                    'c' => 'info',
                                                                ]);
                                                            }
                                                            if ($product->age_group) {
                                                                $specs->put('age', [
                                                                    'k' => 'Age Group',
                                                                    'v' => $product->age_group,
                                                                    'c' => 'primary',
                                                                ]);
                                                            }
                                                        }
                                                        $vAttributes = $variant?->attributes ?? $item->item_snapshot['variant_attributes'] ?? null;
                                                        if ($vAttributes) {
                                                            foreach ($vAttributes as $k => $v) {
                                                                $key = strtolower(str_replace(['_', '-'], ' ', $k));
                                                                if (
                                                                    str_contains($key, 'flav') ||
                                                                    str_contains($key, 'pack') ||
                                                                    str_contains($key, 'age')
                                                                ) {
                                                                    continue;
                                                                }
                                                                $specs->put($key, [
                                                                    'k' => ucfirst($k),
                                                                    'v' => $v,
                                                                    'c' => 'info',
                                                                ]);
                                                            }
                                                        }
                                                    @endphp
                                                    @foreach ($specs as $spec)
                                                        <span
                                                            class="badge bg-{{ $spec['c'] }}-100 text-{{ $spec['c'] }}-600 text-xs">{{ $spec['k'] }}:
                                                            {{ $spec['v'] }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $item->sku }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>INR {{ number_format((float) $item->unit_price, 2) }}</td>
                                        <td>INR {{ number_format((float) $item->tax_amount, 2) }}</td>
                                        <td>INR {{ number_format((float) $item->discount_amount, 2) }}</td>
                                        <td>INR {{ number_format((float) $item->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Amount Breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="amount-flow">
                        <div class="amount-flow-row">
                            <div>
                                <span class="amount-flow-label">Products subtotal</span>
                                <span class="amount-flow-note">Before tax, shipping, coupon, and coins</span>
                            </div>
                            <strong>INR {{ number_format((float) $order->subtotal, 2) }}</strong>
                        </div>
                        <div class="amount-flow-row">
                            <div>
                                <span class="amount-flow-label">Tax / GST</span>
                                <span class="amount-flow-note">CGST INR {{ number_format((float) $order->cgst_total, 2) }}
                                    + SGST INR {{ number_format((float) $order->sgst_total, 2) }} + IGST INR
                                    {{ number_format((float) $order->igst_total, 2) }}</span>
                            </div>
                            <strong>INR {{ number_format((float) $order->tax_total, 2) }}</strong>
                        </div>
                        <div class="amount-flow-row">
                            <div>
                                <span class="amount-flow-label">Shipping</span>
                            </div>
                            <strong>INR {{ number_format((float) $order->shipping_total, 2) }}</strong>
                        </div>
                        <div class="amount-flow-row">
                            <div>
                                <span class="amount-flow-label">Order value before savings</span>
                            </div>
                            <strong>INR {{ number_format($grossTotal, 2) }}</strong>
                        </div>
                        <div class="amount-flow-row text-success-600">
                            <div>
                                <span class="amount-flow-label">Coupon discount</span>
                                <span class="amount-flow-note">{{ $order->coupon_code ?: 'No coupon used' }}</span>
                            </div>
                            <strong>- INR {{ number_format((float) $order->discount_total, 2) }}</strong>
                        </div>
                        <div class="amount-flow-row text-warning-600">
                            <div>
                                <span class="amount-flow-label">NB Coins redeemed</span>
                                <span class="amount-flow-note">{{ number_format((int) $order->coins_redeemed) }} coins
                                    used</span>
                            </div>
                            <strong>- INR {{ number_format((float) $order->coin_discount, 2) }}</strong>
                        </div>
                        <div class="amount-flow-row">
                            <div>
                                <span class="amount-flow-label">Total savings</span>
                            </div>
                            <strong>INR {{ number_format($totalDiscount, 2) }}</strong>
                        </div>
                        <div class="amount-flow-row">
                            <div>
                                <span class="amount-flow-label">Coins earned from this order</span>
                            </div>
                            <strong>{{ number_format($coinsEarned) }} coins</strong>
                        </div>
                        <div class="amount-flow-row bg-primary-50 px-3 rounded-3 mt-2">
                            <div>
                                <span class="amount-flow-label text-primary-600">Final amount to collect</span>
                            </div>
                            <h5 class="mb-0 text-primary-600">INR {{ number_format((float) $order->grand_total, 2) }}</h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Status Timeline</h5>
                </div>
                <div class="card-body">
                    @forelse ($order->statusHistories as $history)
                        <div class="mb-3 pb-3 border-bottom">
                            <div><strong>{{ ucfirst($history->to_status) }}</strong> at
                                {{ optional($history->created_at)->format('d M Y H:i') }}</div>
                            <div class="text-muted">From: {{ ucfirst($history->from_status ?? 'new') }}</div>
                            @if ($history->note)
                                <div>{{ $history->note }}</div>
                            @endif
                        </div>
                    @empty
                        <p class="mb-0">No status history available.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
