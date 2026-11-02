@extends('layouts.user-panel')
@section('title', 'Invoices — NutriBuddy Kids')
@section('panel-page-class', 'panel-order panel-invoices')

@section('panel-content')
    <div class="inner-topbar">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <line x1="3" y1="6" x2="21" y2="6" />
                <line x1="3" y1="12" x2="21" y2="12" />
                <line x1="3" y1="18" x2="21" y2="18" />
            </svg>
        </button>
        <span class="it-title">Billing & Invoices 📑</span>
        <div style="width:36px"></div>
    </div>

    <div class="page">
       

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
                                    <span class="fw-bold" style="color: var(--pk);">INV-{{ $order->order_number }}</span>
                                    <span class="fw-bold" style="color: var(--pk);">{{ $order->invoice_number ?: ('INV-' . $order->order_number) }}</span>
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

    @push('scripts')
        <script>
            function toggleSidebar() {
                document.getElementById('sidebar').classList.toggle('open');
                document.getElementById('overlay').classList.toggle('show');
            }
            function closeSidebar() {
                document.getElementById('sidebar').classList.remove('open');
                document.getElementById('overlay').classList.remove('show');
            }
        </script>
    @endpush
@endsection
