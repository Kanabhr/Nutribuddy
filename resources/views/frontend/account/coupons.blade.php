@extends('layouts.user-panel')
@section('title', 'My Coupons - NutriBuddy Kids')
@section('panel-page-class', 'panel-coupons')
@section('panel-content')

<div class="inner-topbar">
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <line x1="3" y1="6" x2="21" y2="6" />
            <line x1="3" y1="12" x2="21" y2="12" />
            <line x1="3" y1="18" x2="21" y2="18" />
        </svg>
    </button>
    <span class="it-title">My Coupons</span>
    <div style="width:36px"></div>
</div>

<div class="page">
    <p style="color: #64748b; margin-bottom: 24px; font-size: 1.05rem;">
        Coupons you have already used on your NutriBuddy orders.
    </p>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px;">
        @forelse($couponUsages as $usage)
            @php
                $coupon = $usage->coupon;
                $order = $usage->order;
            @endphp
            <div class="coupon-card fade-in disabled">
                <div class="coupon-header">
                    <div class="coupon-code">{{ $coupon->code ?? 'Coupon removed' }}</div>
                    <div class="coupon-value">
                        @if($coupon)
                            {{ $coupon->discount_type === 'percentage' ? $coupon->discount_value . '%' : 'Rs. ' . number_format($coupon->discount_value, 2) }} OFF
                        @else
                            Used
                        @endif
                    </div>
                </div>

                <div class="coupon-title">{{ $coupon->name ?? 'Discount Coupon' }}</div>
                <div class="coupon-desc">
                    @if($order)
                        Applied on order <strong>#{{ $order->order_number }}</strong>.
                    @else
                        This coupon was applied to an older order.
                    @endif
                </div>

                <div class="coupon-footer">
                    <div>Used: {{ $usage->created_at?->format('d M, Y') ?? '-' }}</div>
                    <span class="coupon-badge" style="background:#fee2e2; color:#b91c1c;">Already Used</span>
                </div>
            </div>
        @empty
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: white; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                <div style="font-size: 3rem; margin-bottom: 10px;">Coupon</div>
                <h3 style="color: var(--dk); margin-bottom: 8px;">No Used Coupons Yet</h3>
                <p style="color: #64748b;">Coupons you use during checkout will appear here.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
