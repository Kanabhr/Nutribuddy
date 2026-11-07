@extends('layouts.user-panel')
@section('title', 'Invoices — NutriBuddy Kids')
@section('panel-page-class', 'panel-order panel-invoices')

@push('styles')
<style>
    @media (max-width: 768px) {
        .panel-invoices .orders-card {
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
            box-shadow: none !important;
        }
        .panel-invoices .mobile-orders {
            display: flex !important;
            flex-direction: column;
            gap: 14px;
        }
        .panel-invoices .orders-table {
            display: none !important;
        }
        .panel-invoices .pagination {
            background: #fff;
            border-radius: 16px;
            border: 2px solid var(--border);
            margin-top: 16px;
        }
    }
</style>
@endpush

@section('panel-content')
    <div class="page">
        <!-- PAGE HEADER -->
        <div class="page-header fade-in d1">
            <div class="page-header-left">
                <h1>Billing & Invoices 📑</h1>
                <p>Download and review invoices for your paid orders</p>
            </div>
        </div>

        <div class="orders-card fade-in d1" style="margin-top: 24px;">
            <div style="overflow-x:auto">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
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
                                    <a href="{{ route('user.orders.invoice-page', $order) }}" class="fw-bold" style="color: var(--pk); text-decoration: none;">
                                        {{ $order->invoice_number ?: ('INV-' . $order->order_number) }}
                                    </a>
                                </td>
                                <td>{{ optional($order->placed_at)->format('d/m/Y') ?? $order->created_at->format('d/m/Y') }}</td>
                                <td>#{{ $order->order_number }}</td>
                                <td><strong>₹{{ number_format($order->grand_total, 2) }}</strong></td>
                                <td>
                                    <span class="status-badge {{ $order->payment_status === 'paid' ? 's-delivered' : 's-pending' }}">
                                        {{ strtoupper($order->payment_status) }}
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="{{ route('user.orders.invoice-page', $order) }}" class="act-btn act-view" style="padding: 6px 12px; font-size: 0.75rem;">
                                            View
                                        </a>
                                        <a href="{{ route('user.orders.invoice-download', $order) }}" class="act-btn act-review" style="padding: 6px 12px; font-size: 0.75rem; background: var(--pul); color: var(--pu);">
                                            PDF
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 60px 20px;">
                                    <div style="font-size: 3rem; margin-bottom: 15px;">📑</div>
                                    <h3 style="color: var(--dk);">No Invoices Yet</h3>
                                    <p style="color: var(--muted);">Once you place an order, your official invoices will appear here.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- MOBILE CARDS VIEW -->
            <div class="mobile-orders">
                @forelse($orders as $order)
                    <div class="m-order-card">
                        <div class="m-card-top">
                            <div>
                                <div class="m-id">
                                    <a href="{{ route('user.orders.invoice-page', $order) }}" style="color: var(--pk); text-decoration: none;">
                                        {{ $order->invoice_number ?: ('INV-' . $order->order_number) }}
                                    </a>
                                </div>
                                <div class="m-date">{{ optional($order->placed_at)->format('d/m/Y') ?? $order->created_at->format('d/m/Y') }} · #{{ $order->order_number }}</div>
                            </div>
                            <span class="status-badge {{ $order->payment_status === 'paid' ? 's-delivered' : 's-pending' }}">
                                {{ strtoupper($order->payment_status) }}
                            </span>
                        </div>
                        <div class="m-card-mid">
                            <div class="m-amount">₹{{ number_format($order->grand_total, 2) }}</div>
                        </div>
                        <div class="m-card-actions">
                            <a href="{{ route('user.orders.invoice-page', $order) }}" class="act-btn act-view" style="padding: 6px 12px; font-size: 0.75rem;">View</a>
                            <a href="{{ route('user.orders.invoice-download', $order) }}" class="act-btn act-review" style="padding: 6px 12px; font-size: 0.75rem; background: var(--pul); color: var(--pu);">PDF</a>
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; padding: 60px 20px; width: 100%;">
                        <div style="font-size: 3rem; margin-bottom: 15px;">📑</div>
                        <h3 style="color: var(--dk);">No Invoices Yet</h3>
                        <p style="color: var(--muted);">Once you place an order, your official invoices will appear here.</p>
                    </div>
                @endforelse
            </div>

            <!-- MOBILE CARDS -->
            <div class="mobile-orders">
                @forelse($orders as $order)
                    <div class="m-order-card">
                        <div class="m-card-top">
                            <div>
                                <div class="m-id">
                                    <a href="{{ route('user.orders.invoice-page', $order) }}" style="color: var(--pk); text-decoration: none;">
                                        {{ $order->invoice_number ?: ('INV-' . $order->order_number) }}
                                    </a>
                                </div>
                                <div class="m-date">
                                    Order #{{ $order->order_number }} · {{ optional($order->placed_at)->format('d/m/Y') ?? $order->created_at->format('d/m/Y') }}
                                </div>
                            </div>
                            <span class="status-badge {{ $order->payment_status === 'paid' ? 's-delivered' : 's-pending' }}">
                                {{ strtoupper($order->payment_status) }}
                            </span>
                        </div>
                        <div class="m-card-mid">
                            <div class="m-amount">₹{{ number_format($order->grand_total, 2) }}</div>
                            <small style="color:var(--muted);font-size:.73rem">{{ strtoupper($order->payment_method ?? 'COD') }}</small>
                        </div>
                        <div class="m-card-actions">
                            <a href="{{ route('user.orders.invoice-page', $order) }}" class="act-btn act-view" style="flex:1; justify-content:center; text-decoration:none;">
                                View
                            </a>
                            <a href="{{ route('user.orders.invoice-download', $order) }}" class="act-btn act-review" style="flex:1; justify-content:center; background: var(--pul); color: var(--pu); text-decoration:none;">
                                PDF
                            </a>
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; padding: 40px 20px; background: #fff; border-radius: 16px; border: 2px dashed var(--border);">
                        <div style="font-size: 2.5rem; margin-bottom: 12px;">📑</div>
                        <h4 style="color: var(--dk); margin-bottom: 6px;">No Invoices Yet</h4>
                        <p style="color: var(--muted); font-size: 0.85rem; margin: 0;">Once you place an order, your official invoices will appear here.</p>
                    </div>
                @endforelse
            </div>

            @if($orders->hasPages())
                <div class="pagination">
                    <span class="pag-info">
                        Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} invoices
                    </span>
                    <div class="pag-btns">
                        {{-- Previous Page Link --}}
                        @if ($orders->onFirstPage())
                            <span class="pag-btn" style="opacity: 0.5; cursor: not-allowed;">‹</span>
                        @else
                            <a href="{{ $orders->previousPageUrl() }}" class="pag-btn">‹</a>
                        @endif

                        {{-- Page Links --}}
                        @php
                            $start = max($orders->currentPage() - 2, 1);
                            $end = min($start + 4, $orders->lastPage());
                            if ($end - $start < 4) {
                                $start = max($end - 4, 1);
                            }
                        @endphp

                        @for ($i = $start; $i <= $end; $i++)
                            @if ($i == $orders->currentPage())
                                <span class="pag-btn active">{{ $i }}</span>
                            @else
                                <a href="{{ $orders->url($i) }}" class="pag-btn">{{ $i }}</a>
                            @endif
                        @endfor

                        {{-- Next Page Link --}}
                        @if ($orders->hasMorePages())
                            <a href="{{ $orders->nextPageUrl() }}" class="pag-btn">›</a>
                        @else
                            <span class="pag-btn" style="opacity: 0.5; cursor: not-allowed;">›</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

    </div>
@endsection
