<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice #{{ $order->order_number }}</title>
    <title>Tax Invoice {{ $invoiceNumber ?? ($order->invoice_number ?: ('#' . $order->order_number)) }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            color: #1f2937;
            background: #ffffff;
            padding: 24px;
            line-height: 1.45;
            font-size: 11px;
        }

        .invoice-box {
            width: 100%;
            max-width: 800px;
            margin: auto;
        }

        /* ── TABLE-BASED LAYOUT FOR ROBUST PDF RENDERING ── */
        .layout-table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin-bottom: 20px;
        }

        .layout-table td {
            vertical-align: top;
            padding: 0;
        }

        /* ── HEADER ── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2.5px solid #ec4899;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .company-logo {
            max-height: 80px;
            max-width: 280px;
            height: auto;
            width: auto;
            display: block;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 15px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 2px;
        }

        .company-meta {
            font-size: 10px;
            color: #4b5563;
            line-height: 1.45;
        }

        .legal-tag {
            display: inline-block;
            background: #f3e8ff;
            color: #6b21a8;
            font-size: 9px;
            font-weight: bold;
            padding: 2px 7px;
            border-radius: 4px;
            margin-top: 5px;
            margin-right: 5px;
            border: 1px solid #e9d5ff;
        }

        .invoice-title-block {
            text-align: right;
        }

        .inv-heading {
            font-size: 26px;
            font-weight: 800;
            color: #ec4899;
            letter-spacing: -0.5px;
            line-height: 1;
            margin-bottom: 4px;
        }

        .inv-sub {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
        }

        .inv-meta-line {
            font-size: 10px;
            color: #374151;
            margin: 2px 0;
        }

        .inv-meta-line strong {
            color: #111827;
        }

        .status-badge {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }
        .status-paid   { background: #d1fae5; color: #065f46; }
        .status-unpaid { background: #fef3c7; color: #92400e; }
        .status-cod    { background: #e0e7ff; color: #3730a3; }

        /* ── DETAILS GRID ── */
        .info-grid-table {
            width: 100%;
            border-collapse: collapse;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-grid-table td {
            width: 33.33%;
            padding: 12px 14px;
            vertical-align: top;
            border-right: 1px solid #e5e7eb;
            font-size: 10.5px;
            line-height: 1.45;
        }

        .info-grid-table td:last-child {
            border-right: none;
        }

        .col-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #ec4899;
            font-weight: bold;
            margin-bottom: 6px;
            display: block;
        }

        .muted {
            color: #6b7280;
        }

        /* ── ITEMS TABLE ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th {
            background: #f8fafc;
            padding: 9px 8px;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #475569;
            border-top: 1px solid #e2e8f0;
            border-bottom: 2px solid #cbd5e1;
            font-weight: bold;
        }

        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 10.5px;
            vertical-align: top;
        }

        .items-table tr:last-child td {
            border-bottom: 1px solid #e2e8f0;
        }

        .item-name {
            font-weight: bold;
            color: #111827;
            font-size: 11px;
        }

        .item-variant {
            font-size: 9.5px;
            color: #6b7280;
            margin-top: 2px;
        }

        .item-hsn {
            font-family: monospace;
            font-size: 10px;
            color: #4b5563;
        }

        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .text-left   { text-align: left; }
        .fw-bold     { font-weight: bold; }

        /* ── TAX SUMMARY TABLE ── */
        .tax-summary-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #1f2937;
            margin-bottom: 6px;
        }

        .tax-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .tax-table th {
            background: #fdf2f8;
            color: #9d174d;
            font-size: 9px;
            padding: 6px 8px;
            border: 1px solid #fbcfe8;
            text-align: right;
            text-transform: uppercase;
            font-weight: bold;
        }

        .tax-table th:first-child { text-align: left; }

        .tax-table td {
            padding: 6px 8px;
            border: 1px solid #f3f4f6;
            font-size: 10px;
            color: #374151;
            text-align: right;
        }

        .tax-table td:first-child {
            text-align: left;
            font-weight: bold;
            color: #111827;
        }

        .tax-table tr.total-row td {
            background: #f9fafb;
            font-weight: bold;
            color: #111827;
            border-top: 2px solid #e5e7eb;
        }

        /* ── TOTALS TABLE ── */
        .totals-container {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .totals-container td {
            vertical-align: top;
            padding: 0;
        }

        .totals-table {
            width: 290px;
            border-collapse: collapse;
            margin-left: auto;
        }

        .totals-table td {
            padding: 6px 0;
            font-size: 11px;
            border-bottom: 1px solid #f3f4f6;
        }

        .totals-table td.label-col {
            color: #4b5563;
        }

        .totals-table td.val-col {
            text-align: right;
            font-weight: bold;
            color: #111827;
        }

        .totals-table tr.discount td {
            color: #dc2626;
        }

        .totals-table tr.discount td.val-col {
            color: #dc2626;
        }

        .totals-table tr.coins td {
            color: #ea580c;
        }

        .totals-table tr.coins td.val-col {
            color: #ea580c;
        }

        .totals-table tr.grand-row td {
            padding: 10px 0;
            border-top: 2px solid #111827;
            border-bottom: 2px solid #111827;
            font-size: 14px;
            font-weight: 800;
            color: #7c3aed;
        }

        .totals-table tr.grand-row td.val-col {
            color: #7c3aed;
        }

        /* ── FOOTER ── */
        .footer {
            margin-top: 24px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 16px;
            font-size: 9.5px;
            color: #6b7280;
            line-height: 1.6;
        }

        .footer strong {
            color: #111827;
        }

        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

@php
    // 1. Resolve Logo to Base64 (Rock-solid for DomPDF and headless renderers)
    $logoBase64 = null;
    $customLogoPath = \App\Models\Setting::get('side_section_logo');
    if ($customLogoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($customLogoPath)) {
        $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($customLogoPath);
        if (file_exists($fullPath)) {
            $mime = mime_content_type($fullPath) ?: 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
        }
    }
    if (!$logoBase64) {
        $fallback = public_path('img/logo.png');
        if (file_exists($fallback)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($fallback));
        } elseif (file_exists(public_path('assets/images/logo.png'))) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('assets/images/logo.png')));
        }
    }

    // 2. Resolve User's Real Business Details from Settings (NO Sample Data)
    $companyName = \App\Models\Setting::get('company_name') ?: \App\Models\Setting::get('site_name', 'NutriBuddy');
    $companyAddress = \App\Models\Setting::get('address', '');
    $companyPhone = \App\Models\Setting::get('contact_phone', '');
    $companyEmail = \App\Models\Setting::get('contact_email', '');
    $companyWebsite = \App\Models\Setting::get('website_url') ?: config('app.url', 'www.nutribuddy.in');
    $companyGst = \App\Models\Setting::get('gst_number', '');
    $companyPan = \App\Models\Setting::get('pan_number', '');
    $companyCin = \App\Models\Setting::get('cin_number', '');

    // 3. GST State Determination (Dynamic from Admin Settings)
    $sellerState = \App\Models\Setting::get('store_state', 'Gujarat');
    $customerState = $order->shipping_state ?: ($order->billing_state ?: 'Gujarat');
    $isIntraState = \App\Services\PricingService::isSameState($customerState, $sellerState);

    // 4. Tax Breakdown Summary Calculation
    $taxSummary = [];
    foreach($order->items as $item) {
        $rate = (float) $item->tax_percent;
        $hsn = $item->item_snapshot['hsn_code'] ?? ($item->product?->hsn_code ?? '210690');
        $taxableValue = (float) $item->unit_price * (int) $item->quantity;
        $taxAmt = (float) $item->tax_amount;

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
        $taxSummary[$key]['tax_amount'] += $taxAmt;
    }
    ksort($taxSummary);

    $totalTaxable = collect($taxSummary)->sum('taxable_value');
    $totalTax = collect($taxSummary)->sum('tax_amount');

    $stBadgeClass = $order->payment_status === 'paid' ? 'status-paid'
        : ($order->payment_status === 'cod' ? 'status-cod' : 'status-unpaid');
@endphp

    <div class="invoice-box" id="printableInvoice">

        {{-- ── HEADER TABLE ── --}}
        <table class="header-table">
            <tr>
                <td style="width: 58%; vertical-align: top;">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="NutriBuddy" class="company-logo">
                    @else
                        <div class="company-name" style="font-size: 22px; color: #ec4899; margin-bottom: 6px;">{{ $companyName }}</div>
                    @endif
                    <div class="company-name">{{ $companyName }}</div>
                    <div class="company-meta">
                        @if($companyAddress)
                            {{ $companyAddress }}<br>
                        @endif
                        State: <strong>{{ $sellerState }}</strong> &nbsp;|&nbsp; Country: India<br>
                        @if($companyPhone)
                            📞 {{ $companyPhone }}
                        @endif
                        @if($companyEmail)
                            &nbsp;|&nbsp; ✉️ {{ $companyEmail }}
                        @endif
                        @if($companyWebsite)
                            <br>🌐 {{ $companyWebsite }}
                        @endif
                    </div>
                    @if($companyGst || $companyPan || $companyCin)
                        <div style="margin-top: 6px;">
                            @if($companyGst)
                                <span class="legal-tag">GSTIN: {{ $companyGst }}</span>
                            @endif
                            @if($companyPan)
                                <span class="legal-tag">PAN: {{ $companyPan }}</span>
                            @endif
                            @if($companyCin)
                                <span class="legal-tag">CIN: {{ $companyCin }}</span>
                            @endif
                        </div>
                    @endif
                </td>
                <td style="width: 42%; vertical-align: top;" class="invoice-title-block">
                    <div class="inv-heading">TAX INVOICE</div>
                    <div class="inv-sub">Original For Recipient</div>
                    <div class="inv-meta-line">Invoice #: <strong>#INV-{{ $order->order_number }}</strong></div>
                    <div class="inv-meta-line">Invoice #: <strong>{{ $invoiceNumber ?? ($order->invoice_number ?: ('#INV-' . $order->order_number)) }}</strong></div>
                    <div class="inv-meta-line">Invoice Date: <strong>{{ optional($order->placed_at)->format('d M Y') ?? $order->created_at->format('d M Y') }}</strong></div>
                    <div class="inv-meta-line">Place of Supply: <strong>{{ $customerState }} ({{ $isIntraState ? 'Intra-State' : 'Inter-State' }})</strong></div>
                    <div class="inv-meta-line">Payment Method: <strong>{{ strtoupper($order->payment_method) }}</strong></div>
                    <span class="status-badge {{ $stBadgeClass }}">
                        {{ strtoupper($order->payment_status) }}
                    </span>
                </td>
            </tr>
        </table>

        {{-- ── BILLING & SHIPPING DETAILS ── --}}
        <table class="info-grid-table">
            <tr>
                <td>
                    <span class="col-label">Billed To (Customer)</span>
                    <strong style="color: #111827;">{{ $order->customer_name }}</strong><br>
                    <span class="muted">{{ $order->customer_email }}</span><br>
                    <span class="muted">{{ $order->customer_phone }}</span><br>
                    <span class="muted">State: {{ $customerState }}</span>
                </td>
                <td>
                    <span class="col-label">Shipped To (Delivery)</span>
                    <strong style="color: #111827;">{{ $order->shipping_name }}</strong><br>
                    <span class="muted">{{ $order->shipping_address_line_1 }}</span><br>
                    @if($order->shipping_address_line_2)
                        <span class="muted">{{ $order->shipping_address_line_2 }}<br></span>
                    @endif
                    <span class="muted">{{ $order->shipping_city }}, {{ $order->shipping_state }} - {{ $order->shipping_postal_code }}</span>
                </td>
                <td>
                    <span class="col-label">Order Details</span>
                    Order #: <strong>{{ $order->order_number }}</strong><br>
                    Order Date: {{ optional($order->placed_at)->format('d M Y, h:i A') ?? $order->created_at->format('d M Y') }}<br>
                    Fulfillment: <strong>{{ ucfirst($order->fulfillment_status ?? 'unfulfilled') }}</strong><br>
                    Supply Type: <strong>{{ $isIntraState ? 'CGST + SGST (Intra-State)' : 'IGST (Inter-State)' }}</strong>
                </td>
            </tr>
        </table>

        {{-- ── ITEMS TABLE WITH HSN CODE ── --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 5%;">#</th>
                    <th class="text-left" style="width: 43%;">Item Description</th>
                    <th class="text-center" style="width: 12%;">HSN/SAC</th>
                    <th class="text-center" style="width: 8%;">Qty</th>
                    <th class="text-right" style="width: 12%;">Rate (₹)</th>
                    <th class="text-right" style="width: 10%;">GST</th>
                    <th class="text-right" style="width: 10%;">Total (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $idx => $item)
                    @php
                        $vName = $item->item_snapshot['variant_name'] ?? ($item->productVariant?->name ?? null);
                        $hsn = $item->item_snapshot['hsn_code'] ?? ($item->product?->hsn_code ?? '210690');
                        $taxRate = (float) $item->tax_percent;
                        $taxAmt = (float) $item->tax_amount;
                        $lineTotal = ((float) $item->unit_price * (int) $item->quantity) + $taxAmt;
                    @endphp
                    <tr>
                        <td class="text-center muted">{{ $idx + 1 }}</td>
                        <td>
                            <div class="item-name">{{ $item->product_name }}</div>
                            @if($vName)
                                <div class="item-variant">Variant: {{ $vName }}</div>
                            @endif
                        </td>
                        <td class="text-center item-hsn">{{ $hsn }}</td>
                        <td class="text-center fw-bold">{{ $item->quantity }}</td>
                        <td class="text-right">₹{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">
                            {{ number_format($taxRate, 0) }}%<br>
                            <span style="font-size: 8.5px; color: #6b7280;">₹{{ number_format($taxAmt, 2) }}</span>
                        </td>
                        <td class="text-right fw-bold">₹{{ number_format($lineTotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- ── GST TAX BREAKDOWN TABLE (INTRA VS INTER STATE) ── --}}
        <div class="tax-summary-title">GST Tax Breakdown (Supplier State: {{ $sellerState }} &rarr; Place of Supply: {{ $customerState }})</div>
        <table class="tax-table">
            <thead>
                <tr>
                    <th style="width: 15%;">HSN / SAC</th>
                    <th style="width: 17%;">Taxable Value</th>
                    @if($isIntraState)
                        <th style="width: 17%;">CGST Rate</th>
                        <th style="width: 17%;">CGST Amt</th>
                        <th style="width: 17%;">SGST Rate</th>
                        <th style="width: 17%;">SGST Amt</th>
                    @else
                        <th style="width: 34%;">IGST Rate</th>
                        <th style="width: 34%;">IGST Amt</th>
                    @endif
                    <th style="width: 17%;">Total Tax</th>
                </tr>
            </thead>
            <tbody>
                @foreach($taxSummary as $sum)
                    <tr>
                        <td>{{ $sum['hsn'] }}</td>
                        <td>₹{{ number_format($sum['taxable_value'], 2) }}</td>
                        @if($isIntraState)
                            <td>{{ number_format($sum['rate'] / 2, 2) }}%</td>
                            <td>₹{{ number_format($sum['tax_amount'] / 2, 2) }}</td>
                            <td>{{ number_format($sum['rate'] / 2, 2) }}%</td>
                            <td>₹{{ number_format($sum['tax_amount'] / 2, 2) }}</td>
                        @else
                            <td>{{ number_format($sum['rate'], 2) }}%</td>
                            <td>₹{{ number_format($sum['tax_amount'], 2) }}</td>
                        @endif
                        <td>₹{{ number_format($sum['tax_amount'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>Total</td>
                    <td>₹{{ number_format($totalTaxable, 2) }}</td>
                    @if($isIntraState)
                        <td>-</td>
                        <td>₹{{ number_format($totalTax / 2, 2) }}</td>
                        <td>-</td>
                        <td>₹{{ number_format($totalTax / 2, 2) }}</td>
                    @else
                        <td>-</td>
                        <td>₹{{ number_format($totalTax, 2) }}</td>
                    @endif
                    <td>₹{{ number_format($totalTax, 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- ── TOTALS TABLE ── --}}
        <table class="totals-container">
            <tr>
                <td style="width: 55%; vertical-align: top; padding-right: 20px;">
                    <div style="font-size: 10px; color: #4b5563; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px;">
                        <strong>Tax Treatment:</strong><br>
                        @if($isIntraState)
                            Intra-state supply within {{ $sellerState }}. Tax split equally into Central GST (CGST) and State GST (SGST).
                        @else
                            Inter-state supply from {{ $sellerState }} to {{ $customerState }}. Integrated GST (IGST) applied at full rate.
                        @endif
                    </div>
                </td>
                <td style="width: 45%; vertical-align: top;">
                    <table class="totals-table">
                        <tr>
                            <td class="label-col">Taxable Subtotal</td>
                            <td class="val-col">₹{{ number_format((float) $order->subtotal, 2) }}</td>
                        </tr>
                        @if((float) $order->discount_total > 0.01)
                            <tr class="discount">
                                <td class="label-col">Coupon Discount {{ $order->coupon_code ? '(' . $order->coupon_code . ')' : '' }}</td>
                                <td class="val-col">- ₹{{ number_format((float) $order->discount_total, 2) }}</td>
                            </tr>
                        @endif
                        @if((float) $order->coin_discount > 0.01)
                            <tr class="coins">
                                <td class="label-col">NB Coins Discount {{ (int) $order->coins_redeemed > 0 ? '(' . number_format((int) $order->coins_redeemed) . ' coins)' : '' }}</td>
                                <td class="val-col">- ₹{{ number_format((float) $order->coin_discount, 2) }}</td>
                            </tr>
                        @endif
                        @if($isIntraState)
                            <tr>
                                <td class="label-col">CGST</td>
                                <td class="val-col">₹{{ number_format($totalTax / 2, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="label-col">SGST</td>
                                <td class="val-col">₹{{ number_format($totalTax / 2, 2) }}</td>
                            </tr>
                        @else
                            <tr>
                                <td class="label-col">IGST</td>
                                <td class="val-col">₹{{ number_format($totalTax, 2) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="label-col">Shipping</td>
                            <td class="val-col">{{ (float) $order->shipping_total > 0 ? '₹' . number_format((float) $order->shipping_total, 2) : 'FREE' }}</td>
                        </tr>
                        <tr class="grand-row">
                            <td class="label-col">Grand Total</td>
                            <td class="val-col">₹{{ number_format(round((float) $order->grand_total)) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- ── FOOTER ── --}}
        <div class="footer">
            <p><strong>{{ $companyName }}</strong></p>
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
                <p>{{ implode(' &nbsp;|&nbsp; ', $footerLegals) }}</p>
            @endif
            @if(!empty($footerContacts))
                <p>{{ implode(' &nbsp;|&nbsp; ', $footerContacts) }}</p>
            @endif
            <p style="margin-top: 8px;">This is a computer-generated tax invoice and does not require a physical signature.</p>
        </div>

    </div>

    <script>
        if (window.location.search.indexOf('print=1') > -1) {
            window.print();
        }
    </script>
</body>
</html>
