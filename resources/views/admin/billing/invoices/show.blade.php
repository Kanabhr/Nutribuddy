@extends('layouts.admin')
@php
    $title = 'Invoice #INV-' . $order->order_number;
    $subTitle = 'Ecommerce / Invoices / INV-' . $order->order_number;
    $displayInvoiceNo = $order->invoice_number ?: ('#INV-' . $order->order_number);
    $title = 'Invoice ' . $displayInvoiceNo;
    $subTitle = 'Ecommerce / Invoices / ' . $displayInvoiceNo;
@endphp


<style>
    /* ═══════════════════════════════════════════════════════
           ALL SELECTORS SCOPED UNDER .inv-wrap TO BEAT BOOTSTRAP
           ═══════════════════════════════════════════════════════ */

    .inv-wrap * {
        box-sizing: border-box !important;
    }

    .inv-wrap {
        font-family: 'Inter', 'Segoe UI', sans-serif !important;
        max-width: 980px !important;
        margin: 0 auto !important;
        padding: 0 16px 40px !important;
    }

    /* ── Action bar ── */
    .inv-wrap .inv-actions {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        margin-bottom: 20px !important;
        flex-wrap: wrap !important;
        gap: 10px !important;
    }

    /* ── Card ── */
    .inv-wrap .inv-card {
        background: #fff !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 16px !important;
        overflow: hidden !important;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06) !important;
        position: relative !important;
    }

    .inv-wrap .inv-card-topbar {
        height: 6px !important;
        background: linear-gradient(90deg, #ec4899, #8b5cf6) !important;
        padding: 0 !important;
        border: none !important;
    }

    .inv-wrap .inv-body {
        padding: 36px 40px !important;
    }

    /* ══════════════════
           HEADER
        ══════════════════ */
    .inv-wrap .inv-header {
        width: 100% !important;
        margin-bottom: 28px !important;
        padding-bottom: 24px !important;
        border-bottom: 2px solid #f3f4f6 !important;
        display: table !important;
        table-layout: fixed !important;
    }

    .inv-wrap .inv-company-left {
        display: table-cell !important;
        vertical-align: top !important;
        width: 58% !important;
    }

    .inv-wrap .inv-title-block {
        display: table-cell !important;
        vertical-align: top !important;
        text-align: right !important;
        width: 42% !important;
        padding-left: 24px !important;
    }

    .inv-wrap .inv-company-logo img {
        height: auto !important;
        max-height: 80px !important;
        max-width: 280px !important;
        width: auto !important;
        object-fit: contain !important;
        display: block !important;
        margin-bottom: 12px !important;
    }

    .inv-wrap .inv-company-name {
        font-size: 15px !important;
        font-weight: 800 !important;
        color: #111827 !important;
        margin: 0 0 6px 0 !important;
        line-height: 1.4 !important;
    }

    .inv-wrap .inv-company-meta {
        font-size: 12px !important;
        color: #6b7280 !important;
        line-height: 1.8 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .inv-wrap .inv-company-meta span {
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .inv-wrap .inv-legal-tags {
        margin-top: 12px !important;
        line-height: 1 !important;
    }

    .inv-wrap .inv-legal-tag {
        display: inline-block !important;
        background: #ede9fe !important;
        color: #5b21b6 !important;
        font-size: 10px !important;
        font-weight: 700 !important;
        padding: 3px 9px !important;
        border-radius: 4px !important;
        letter-spacing: 0.3px !important;
        margin-right: 5px !important;
        margin-bottom: 5px !important;
        line-height: 1.6 !important;
    }

    /* Right: Invoice title */
    .inv-wrap .inv-word {
        font-size: 40px !important;
        font-weight: 900 !important;
        color: #111827 !important;
        letter-spacing: -1.5px !important;
        line-height: 1 !important;
        margin: 0 0 8px 0 !important;
        padding: 0 !important;
    }

    .inv-wrap .inv-num {
        font-size: 14px !important;
        color: #6b7280 !important;
        margin: 0 0 4px 0 !important;
        padding: 0 !important;
        font-weight: 500 !important;
    }

    .inv-wrap .inv-date {
        font-size: 12px !important;
        color: #9ca3af !important;
        margin: 0 0 14px 0 !important;
        padding: 0 !important;
    }

    .inv-wrap .inv-status-pill {
        display: inline-block !important;
        padding: 5px 16px !important;
        border-radius: 20px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        letter-spacing: 0.5px !important;
        line-height: 1.4 !important;
    }

    .inv-wrap .inv-status-paid {
        background: #d1fae5 !important;
        color: #065f46 !important;
    }

    .inv-wrap .inv-status-unpaid {
        background: #fef3c7 !important;
        color: #92400e !important;
    }

    .inv-wrap .inv-status-cod {
        background: #e0e7ff !important;
        color: #3730a3 !important;
    }

    /* ══════════════════
           INFO GRID
        ══════════════════ */
    .inv-wrap .inv-info-grid {
        width: 100% !important;
        background: #f9fafb !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 12px !important;
        margin-bottom: 28px !important;
        border-collapse: collapse !important;
        border-spacing: 0 !important;
        overflow: hidden !important;
        /* Override Bootstrap table styles */
        display: table !important;
    }

    .inv-wrap .inv-info-grid tr {
        background: transparent !important;
        border: none !important;
    }

    .inv-wrap .inv-info-grid td {
        width: 33.33% !important;
        vertical-align: top !important;
        padding: 20px 22px !important;
        border-right: 1px solid #e5e7eb !important;
        border-bottom: none !important;
        border-top: none !important;
        background: transparent !important;
    }

    .inv-wrap .inv-info-grid td:last-child {
        border-right: none !important;
    }

    .inv-wrap .col-label {
        font-size: 10px !important;
        text-transform: uppercase !important;
        letter-spacing: 1.5px !important;
        font-weight: 800 !important;
        color: #ec4899 !important;
        margin-bottom: 10px !important;
        display: block !important;
        line-height: 1.4 !important;
        padding: 0 !important;
    }

    /* CRITICAL: Override Bootstrap p margins inside info grid */
    .inv-wrap .inv-info-grid td p,
    .inv-wrap .inv-info-grid td p.text-muted-sm {
        font-size: 13px !important;
        color: #374151 !important;
        margin: 0 0 4px 0 !important;
        padding: 0 !important;
        line-height: 1.5 !important;
        border: none !important;
        background: none !important;
    }

    .inv-wrap .inv-info-grid td p.text-muted-sm {
        font-size: 12px !important;
        color: #6b7280 !important;
    }

    /* ══════════════════
           ITEMS TABLE
        ══════════════════ */
    .inv-wrap .inv-table-wrap {
        overflow-x: auto !important;
        margin-bottom: 28px !important;
        border-radius: 8px !important;
        border: 1px solid #e5e7eb !important;
    }

    .inv-wrap .inv-table {
        width: 100% !important;
        border-collapse: collapse !important;
        border-spacing: 0 !important;
        margin: 0 !important;
        font-size: 13px !important;
        min-width: 600px !important;
    }

    /* Override Bootstrap thead styles */
    .inv-wrap .inv-table thead,
    .inv-wrap .inv-table thead tr {
        background: #f9fafb !important;
        border-bottom: 2px solid #e5e7eb !important;
    }

    .inv-wrap .inv-table th {
        padding: 13px 14px !important;
        text-align: left !important;
        font-size: 10px !important;
        text-transform: uppercase !important;
        letter-spacing: 1px !important;
        font-weight: 700 !important;
        color: #6b7280 !important;
        white-space: nowrap !important;
        border: none !important;
        border-bottom: 2px solid #e5e7eb !important;
        background: #f9fafb !important;
        vertical-align: middle !important;
    }

    .inv-wrap .inv-table th.r {
        text-align: right !important;
    }

    .inv-wrap .inv-table th.c {
        text-align: center !important;
    }

    /* Override Bootstrap tbody tr styles */
    .inv-wrap .inv-table tbody tr {
        border-bottom: 1px solid #f3f4f6 !important;
        background: #fff !important;
        transition: background 0.15s !important;
    }

    .inv-wrap .inv-table tbody tr:last-child {
        border-bottom: none !important;
    }

    .inv-wrap .inv-table tbody tr:hover {
        background: #fafafa !important;
    }

    /* Override Bootstrap td styles */
    .inv-wrap .inv-table td {
        padding: 14px !important;
        vertical-align: top !important;
        color: #1f2937 !important;
        border: none !important;
        border-bottom: 1px solid #f3f4f6 !important;
        background: transparent !important;
    }

    .inv-wrap .inv-table td.r {
        text-align: right !important;
    }

    .inv-wrap .inv-table td.c {
        text-align: center !important;
    }

    .inv-wrap .inv-prod-name {
        font-weight: 700 !important;
        font-size: 13px !important;
        color: #111827 !important;
        margin: 0 0 3px 0 !important;
        padding: 0 !important;
        line-height: 1.4 !important;
    }

    .inv-wrap .inv-prod-var {
        font-size: 11px !important;
        color: #9ca3af !important;
        margin: 0 0 5px 0 !important;
        padding: 0 !important;
    }

    .inv-wrap .inv-prod-specs {
        margin-top: 6px !important;
    }

    .inv-wrap .inv-spec-pill {
        display: inline-block !important;
        background: #ede9fe !important;
        color: #5b21b6 !important;
        font-size: 10px !important;
        font-weight: 700 !important;
        padding: 2px 7px !important;
        border-radius: 4px !important;
        margin-right: 4px !important;
        margin-bottom: 3px !important;
        line-height: 1.6 !important;
    }

    .inv-wrap .inv-qty {
        font-weight: 700 !important;
        color: #374151 !important;
    }

    .inv-wrap .inv-disc {
        color: #ef4444 !important;
        font-weight: 600 !important;
    }

    .inv-wrap .inv-gst-rate {
        display: block !important;
        color: #374151 !important;
        font-weight: 600 !important;
        margin: 0 !important;
    }

    .inv-wrap .inv-gst-amt {
        display: block !important;
        font-size: 11px !important;
        color: #9ca3af !important;
        margin: 2px 0 0 0 !important;
    }

    .inv-wrap .inv-amount {
        font-weight: 800 !important;
        color: #111827 !important;
    }

    /* ══════════════════
           TOTALS
        ══════════════════ */
    .inv-wrap .inv-totals-row {
        margin-bottom: 32px !important;
        text-align: right !important;
    }

    .inv-wrap .inv-totals-box {
        display: inline-block !important;
        width: 320px !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 12px !important;
        overflow: hidden !important;
        text-align: left !important;
    }

    .inv-wrap .inv-total-line {
        display: table !important;
        width: 100% !important;
        padding: 11px 18px !important;
        font-size: 13px !important;
        color: #374151 !important;
        border-bottom: 1px solid #f3f4f6 !important;
        box-sizing: border-box !important;
        background: #fff !important;
        margin: 0 !important;
    }

    .inv-wrap .inv-total-line:last-child {
        border-bottom: none !important;
    }

    .inv-wrap .inv-total-line .tl-label,
    .inv-wrap .inv-total-line .tl-value {
        display: table-cell !important;
        vertical-align: middle !important;
        padding: 0 !important;
        border: none !important;
        background: none !important;
        color: inherit !important;
        font-size: inherit !important;
    }

    .inv-wrap .inv-total-line .tl-value {
        text-align: right !important;
        font-weight: 700 !important;
    }

    .inv-wrap .inv-total-line.discount {
        color: #ef4444 !important;
        background: #fff5f5 !important;
    }

    .inv-wrap .inv-total-line.grand-tot {
        background: linear-gradient(135deg, #fdf2f8, #f5f3ff) !important;
        padding: 16px 18px !important;
        border-bottom: none !important;
    }

    .inv-wrap .inv-total-line.grand-tot .tl-label,
    .inv-wrap .inv-total-line.grand-tot .tl-value {
        color: #7c3aed !important;
        font-weight: 800 !important;
        font-size: 16px !important;
    }

    /* ══════════════════
           FOOTER
        ══════════════════ */
    .inv-wrap .inv-footer {
        text-align: center !important;
        padding-top: 22px !important;
        border-top: 1px solid #f3f4f6 !important;
        font-size: 11px !important;
        color: #9ca3af !important;
        line-height: 2 !important;
        margin: 0 !important;
    }

    .inv-wrap .inv-footer div {
        margin: 0 !important;
        padding: 0 !important;
    }

    .inv-wrap .inv-footer strong {
        color: #6b7280 !important;
    }

    /* ── Watermark ── */
    .inv-wrap .inv-watermark {
        position: absolute !important;
        top: 18px !important;
        right: 22px !important;
        font-size: 9px !important;
        font-weight: 800 !important;
        letter-spacing: 2px !important;
        color: #e5e7eb !important;
        pointer-events: none !important;
        z-index: 0 !important;
    }

    /* ══════════════════
           RESPONSIVE
        ══════════════════ */
    @media (max-width: 768px) {
        .inv-wrap .inv-body {
            padding: 20px !important;
        }

        .inv-wrap .inv-header {
            display: block !important;
        }

        .inv-wrap .inv-company-left {
            display: block !important;
            width: 100% !important;
            margin-bottom: 20px !important;
        }

        .inv-wrap .inv-title-block {
            display: block !important;
            width: 100% !important;
            text-align: left !important;
            padding-left: 0 !important;
        }

        .inv-wrap .inv-info-grid {
            display: block !important;
        }

        .inv-wrap .inv-info-grid td {
            display: block !important;
            width: 100% !important;
            border-right: none !important;
            border-bottom: 1px solid #f3f4f6 !important;
        }

        .inv-wrap .inv-totals-box {
            width: 100% !important;
        }
    }
    .inv-wrap .inv-tax-table {
        width: 100% !important;
        margin-top: 32px !important;
        border-collapse: collapse !important;
    }

    .inv-wrap .inv-tax-table th {
        background: #f8fafc !important;
        color: #475569 !important;
        font-size: 10px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        padding: 8px 12px !important;
        border: 1px solid #e2e8f0 !important;
        text-align: right !important;
    }

    .inv-wrap .inv-tax-table th:first-child {
        text-align: left !important;
    }

    .inv-wrap .inv-tax-table td {
        padding: 8px 12px !important;
        border: 1px solid #e2e8f0 !important;
        font-size: 11px !important;
        color: #334155 !important;
        text-align: right !important;
    }

    .inv-wrap .inv-tax-table td:first-child {
        text-align: left !important;
        font-weight: 600 !important;
    }

    .inv-wrap .inv-tax-table tr.total-row td {
        background: #f1f5f9 !important;
        font-weight: 700 !important;
    }
</style>
@section('content')
    <div class="inv-wrap">

        {{-- ── Action bar ── --}}
        <div class="inv-actions">
            <a href="{{ route('admin.ecommerce.orders.index') }}"
                class="btn btn-sm btn-secondary-light radius-8 d-inline-flex align-items-center gap-1">
                <iconify-icon icon="lucide:arrow-left"></iconify-icon> Back to Orders
            </a>
            <button onclick="openPrintable()"
                class="btn btn-sm btn-primary radius-8 d-inline-flex align-items-center gap-1">
                <iconify-icon icon="lucide:printer"></iconify-icon> Print / Download PDF
            </button>
        </div>

        {{-- ── Invoice card ── --}}
        <div class="inv-card">
            <div class="inv-card-topbar"></div>
            <span class="inv-watermark">ADMIN COPY</span>

            <div class="inv-body">

                {{-- ══ HEADER ══ --}}
                @php
                    $sideSectionLogo = \App\Models\Setting::get('side_section_logo');
                    $sideSectionLogoExists = $sideSectionLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($sideSectionLogo);
                    $logoUrl = $sideSectionLogoExists
                        ? asset('storage/' . $sideSectionLogo)
                        : asset('assets/images/logo.png');

                    $companyName = \App\Models\Setting::get('company_name') ?: \App\Models\Setting::get('site_name', 'NutriBuddy');
                    $companyAddress = \App\Models\Setting::get('address', '');
                    $companyPhone = \App\Models\Setting::get('contact_phone', '');
                    $companyEmail = \App\Models\Setting::get('contact_email', '');
                    $companyWebsite = \App\Models\Setting::get('website_url') ?: config('app.url', 'www.nutribuddy.in');
                    $companyGst = \App\Models\Setting::get('gst_number', '');
                    $companyPan = \App\Models\Setting::get('pan_number', '');
                    $companyCin = \App\Models\Setting::get('cin_number', '');

                    $sellerState = \App\Models\Setting::get('store_state', 'Gujarat');
                    $customerState = $order->shipping_state ?: ($order->billing_state ?: 'Gujarat');
                    $isIntraState = \App\Services\PricingService::isSameState($customerState, $sellerState);
                @endphp
                <div class="inv-header">

                    <div class="inv-company-left">
                        <div class="inv-company-logo">
                            <img src="{{ $logoUrl }}" alt="{{ $companyName }}" style="max-height: 80px; max-width: 280px; width: auto; height: auto; object-fit: contain;">
                        </div>
                        <div class="inv-company-name">{{ $companyName }}</div>
                        <div class="inv-company-meta">
                            @if($companyAddress)
                                <span>{{ $companyAddress }}</span>
                            @endif
                            <span>State: <strong>{{ $sellerState }}</strong> &nbsp;|&nbsp; Country: India</span>
                            <span>
                                @if($companyPhone) 📞 {{ $companyPhone }} @endif
                                @if($companyEmail) &nbsp; ✉️ {{ $companyEmail }} @endif
                            </span>
                            @if($companyWebsite)
                                <span>🌐 {{ $companyWebsite }}</span>
                            @endif
                        </div>
                        @if($companyGst || $companyPan || $companyCin)
                            <div class="inv-legal-tags">
                                @if($companyGst)
                                    <span class="inv-legal-tag">GSTIN: {{ $companyGst }}</span>
                                @endif
                                @if($companyPan)
                                    <span class="inv-legal-tag">PAN: {{ $companyPan }}</span>
                                @endif
                                @if($companyCin)
                                    <span class="inv-legal-tag">CIN: {{ $companyCin }}</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="inv-title-block">
                        <div class="inv-word">TAX INVOICE</div>
                        <div class="inv-num">#INV-{{ $order->order_number }}</div>
                        <div class="inv-num">{{ $order->invoice_number ?: ('#INV-' . $order->order_number) }}</div>
                        <div class="inv-date">
                            Issued:
                            {{ optional($order->placed_at)->format('d M Y') ?? $order->created_at->format('d M Y') }}
                        </div>
                        @php
                            $stClass = $order->payment_status === 'paid' ? 'inv-status-paid'
                                : ($order->payment_status === 'cod' ? 'inv-status-cod' : 'inv-status-unpaid');
                        @endphp
                        <span class="inv-status-pill {{ $stClass }}">
                            {{ strtoupper($order->payment_status) }}
                        </span>
                    </div>

                </div>{{-- /header --}}

                {{-- ══ INFO GRID ══ --}}
                <table class="inv-info-grid">
                    <tr>
                        <td>
                            <span class="col-label">Billed To</span>
                            <p><strong>{{ $order->customer_name }}</strong></p>
                            <p class="text-muted-sm">{{ $order->customer_email }}</p>
                            <p class="text-muted-sm">{{ $order->customer_phone }}</p>
                        </td>
                        <td>
                            <span class="col-label">Shipped To</span>
                            <p><strong>{{ $order->shipping_name }}</strong></p>
                            <p class="text-muted-sm">{{ $order->shipping_address_line_1 }}</p>
                            @if($order->shipping_address_line_2)
                                <p class="text-muted-sm">{{ $order->shipping_address_line_2 }}</p>
                            @endif
                            <p class="text-muted-sm">
                                {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}
                            </p>
                        </td>
                        <td>
                            <span class="col-label">Payment</span>
                            <p>Method: <strong>{{ strtoupper($order->payment_method) }}</strong></p>
                            <p>Status:
                                <span class="inv-status-pill {{ $stClass }}"
                                    style="font-size:10px !important; padding:2px 10px !important;">
                                    {{ strtoupper($order->payment_status) }}
                                </span>
                            </p>
                            <p class="text-muted-sm">Order #: {{ $order->order_number }}</p>
                        </td>
                    </tr>
                </table>

                {{-- ══ ITEMS TABLE ══ --}}
                <div class="inv-table-wrap">
                    <table class="inv-table">
                        <thead>
                            <tr>
                                <th style="width:38%">Product Description</th>
                                <th class="c" style="width:12%">HSN/SAC</th>
                                <th class="c" style="width:8%">Qty</th>
                                <th class="r" style="width:14%">Unit Price</th>
                                <th class="r" style="width:14%">GST</th>
                                <th class="r" style="width:14%">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                @php
                                    $vName = $item->item_snapshot['variant_name'] ?? ($item->productVariant?->name ?? null);
                                    $hsn = $item->item_snapshot['hsn_code'] ?? ($item->product?->hsn_code ?? '210690');
                                    $variant = $item->productVariant;
                                    $product = $item->product;
                                    $taxRate = $item->tax_percent;
                                    $gstAmt = $item->tax_amount;
                                    $lineTotal = $item->line_total;
                                    $specs = [];
                                    if ($product) {
                                        if ($product->flavor || $product->flavour)
                                            $specs[] = 'Flavor: ' . ($product->flavor ?? $product->flavour);
                                        if ($product->pack_size)
                                            $specs[] = 'Pack Size: ' . $product->pack_size;
                                        if ($product->age_group)
                                            $specs[] = 'Age Group: ' . $product->age_group;
                                    }
                                    if ($variant && $variant->attributes) {
                                        foreach ($variant->attributes as $k => $v) {
                                            $key = strtolower(str_replace(['_', '-'], ' ', $k));
                                            if (str_contains($key, 'flav') || str_contains($key, 'pack') || str_contains($key, 'age'))
                                                continue;
                                            $specs[] = ucfirst($k) . ': ' . $v;
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <div class="inv-prod-name">{{ $item->product_name }}</div>
                                        @if($vName)
                                            <div class="inv-prod-var">Variant: {{ $vName }}</div>
                                        @endif
                                        @if($specs)
                                            <div class="inv-prod-specs">
                                                @foreach($specs as $spec)
                                                    <span class="inv-spec-pill">{{ $spec }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="c" style="font-family: monospace; font-size: 11px; color: #4b5563;">{{ $hsn }}</td>
                                    <td class="c inv-qty">{{ $item->quantity }}</td>
                                    <td class="r">₹{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="r">
                                        <span class="inv-gst-rate">{{ number_format($taxRate, 2) }}%</span>
                                        <span class="inv-gst-amt">₹{{ number_format($gstAmt, 2) }}</span>
                                    </td>
                                    <td class="r inv-amount">₹{{ number_format($item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @php
                    $taxSummary = [];
                    foreach($order->items as $item) {
                        $rate = (float) $item->tax_percent;
                        $hsn = $item->item_snapshot['hsn_code'] ?? ($item->product?->hsn_code ?? '210690');
                        $taxableValue = (float) $item->unit_price * (int) $item->quantity;
                        $taxAmount = (float) $item->tax_amount;

                        $key = $hsn . '_' . $rate;
                        if (!isset($taxSummary[$key])) {
                            $taxSummary[$key] = [
                                'hsn' => $hsn,
                                'rate' => $rate,
                                'taxable_value' => 0.0,
                                'tax_amount' => 0.0,
                            ];
                        }
                        $taxSummary[$key]['taxable_value'] += $taxableValue;
                        $taxSummary[$key]['tax_amount'] += $taxAmount;
                    }
                    ksort($taxSummary);
                    $totalTaxable = collect($taxSummary)->sum('taxable_value');
                    $totalTax = collect($taxSummary)->sum('tax_amount');
                @endphp

                {{-- ══ TAX SUMMARY TABLE ══ --}}
                <div style="margin-bottom: 30px;">
                    <div style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;">
                        GST Tax Breakdown (Supplier State: {{ $sellerState }} &rarr; Place of Supply: {{ $customerState }})
                    </div>
                    <table class="inv-tax-table">
                        <thead>
                            <tr>
                                <th>HSN / SAC</th>
                                <th>Taxable Value</th>
                                @if($isIntraState)
                                    <th>CGST Rate</th>
                                    <th>CGST Amt</th>
                                    <th>SGST Rate</th>
                                    <th>SGST Amt</th>
                                @else
                                    <th>IGST Rate</th>
                                    <th>IGST Amt</th>
                                @endif
                                <th>Total Tax</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($taxSummary as $summary)
                                <tr>
                                    <td style="font-family: monospace;">{{ $summary['hsn'] }}</td>
                                    <td>₹{{ number_format($summary['taxable_value'], 2) }}</td>
                                    @if($isIntraState)
                                        <td>{{ number_format($summary['rate'] / 2, 2) }}%</td>
                                        <td>₹{{ number_format($summary['tax_amount'] / 2, 2) }}</td>
                                        <td>{{ number_format($summary['rate'] / 2, 2) }}%</td>
                                        <td>₹{{ number_format($summary['tax_amount'] / 2, 2) }}</td>
                                    @else
                                        <td>{{ number_format($summary['rate'], 2) }}%</td>
                                        <td>₹{{ number_format($summary['tax_amount'], 2) }}</td>
                                    @endif
                                    <td style="font-weight: 600;">₹{{ number_format($summary['tax_amount'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="total-row">
                                <td>Total</td>
                                <td>₹{{ number_format($totalTaxable, 2) }}</td>
                                @if($isIntraState)
                                    <td></td>
                                    <td>₹{{ number_format($totalTax / 2, 2) }}</td>
                                    <td></td>
                                    <td>₹{{ number_format($totalTax / 2, 2) }}</td>
                                @else
                                    <td></td>
                                    <td>₹{{ number_format($totalTax, 2) }}</td>
                                @endif
                                <td>₹{{ number_format($totalTax, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- ══ TOTALS ══ --}}
                <div class="inv-totals-row">
                    <div class="inv-totals-box">
                        @php
                            // 1. Calculate the REAL original GST for the whole order
                            $totalOriginalGst = $order->items->sum(function($i) {
                                $rate = $i->tax_percent ?? 0;
                                return ($i->unit_price * $i->quantity * $rate) / 100;
                            });

                            // Show the same stored order math used at checkout.
                            $displaySubtotal = (float) $order->subtotal;

                            $totalSavings = (float) $order->discount_total + (float) $order->coin_discount;

                            // 4. Distribute savings
                            $dbCoupon = $order->discount_total;
                            $dbCoins = $order->coin_discount;
                            $totalDbDiscount = $dbCoupon + $dbCoins;

                            $displayCouponDiscount = (float) $order->discount_total;
                            $displayCoinDiscount = (float) $order->coin_discount;
                        @endphp
                        
                        <div class="inv-total-line">
                            <span class="tl-label">Subtotal</span>
                            <span class="tl-value">₹{{ number_format($displaySubtotal, 2) }}</span>
                        </div>

                        @if($displayCouponDiscount > 0.01)
                            <div class="inv-total-line discount">
                                <span class="tl-label">Coupon Discount</span>
                                <span class="tl-value">− ₹{{ number_format($displayCouponDiscount, 2) }}</span>
                            </div>
                        @endif

                        @if($displayCoinDiscount > 0.01)
                            <div class="inv-total-line" style="color: #f97316; background: #fffaf0;">
                                <span class="tl-label">NB Coins Discount ({{ number_format((int) $order->coins_redeemed) }} coins)</span>
                                <span class="tl-value">− ₹{{ number_format($displayCoinDiscount, 2) }}</span>
                            </div>
                        @endif

                        @if($isIntraState)
                            <div class="inv-total-line">
                                <span class="tl-label">CGST (Central Tax)</span>
                                <span class="tl-value">₹{{ number_format($totalTax / 2, 2) }}</span>
                            </div>
                            <div class="inv-total-line">
                                <span class="tl-label">SGST (State Tax)</span>
                                <span class="tl-value">₹{{ number_format($totalTax / 2, 2) }}</span>
                            </div>
                        @else
                            <div class="inv-total-line">
                                <span class="tl-label">IGST (Integrated Tax)</span>
                                <span class="tl-value">₹{{ number_format($totalTax, 2) }}</span>
                            </div>
                        @endif

                        <div class="inv-total-line">
                            <span class="tl-label">Shipping</span>
                            <span class="tl-value">₹{{ number_format($order->shipping_total, 2) }}</span>
                        </div>

                        <div class="inv-total-line grand-tot">
                            <span class="tl-label">Grand Total</span>
                            <span class="tl-value">₹{{ number_format($order->grand_total, 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- ══ FOOTER ══ --}}
                <div class="inv-footer">
                    <div><strong>{{ $companyName }}</strong></div>
                    @php
                        $footerLegals = array_filter([
                            $companyGst ? 'GSTIN: ' . $companyGst : null,
                            $companyPan ? 'PAN: ' . $companyPan : null,
                            $companyCin ? 'CIN: ' . $companyCin : null,
                        ]);
                        $footerContacts = array_filter([
                            $companyEmail ?: null,
                            $companyPhone ?: null,
                            $companyWebsite ?: null,
                        ]);
                    @endphp
                    @if(!empty($footerLegals))
                        <div>{{ implode(' &nbsp;·&nbsp; ', $footerLegals) }}</div>
                    @endif
                    @if(!empty($footerContacts))
                        <div>{{ implode(' &nbsp;·&nbsp; ', $footerContacts) }}</div>
                    @endif
                    <div style="margin-top:8px !important; font-size:10px !important;">
                        This is a computer-generated invoice and does not require a physical signature.
                    </div>
                </div>

            </div>{{-- /inv-body --}}
        </div>{{-- /inv-card --}}
    </div>{{-- /inv-wrap --}}
@endsection


<script>
    function openPrintable() {
        var url = '{{ route('admin.ecommerce.orders.invoice-download', ['order' => $order->id]) }}?print=1';
        window.open(url, '_blank');
    }
</script>
