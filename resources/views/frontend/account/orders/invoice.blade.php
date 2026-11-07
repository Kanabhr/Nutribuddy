@extends('layouts.user-panel')
@section('title', 'Invoice Details — NutriBuddy Kids')
@section('panel-page-class', 'panel-order panel-order-invoice')

@section('panel-content')

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
@endphp

    </div>

    <div class="page">
        <div class="inv-wrap fade-in">
            <div class="inv-card">
                <div class="inv-card-topbar"></div>

                <div class="inv-body">
                    {{-- HEADER --}}
                    <div class="inv-header">
                        <div class="inv-company-left">
                            <div class="inv-company-logo">
                                <img src="{{ $logoUrl }}" alt="{{ $companyName }}" style="max-height: 80px; max-width: 280px; width: auto; height: auto; object-fit: contain; margin-bottom: 10px;">
                            </div>
                            <div class="inv-company-name">{{ $companyName }}</div>
                            <div class="inv-company-meta">
                                @if($companyAddress)
                                    <span>{{ $companyAddress }}</span><br>
                                @endif
                                <span>State: <strong>{{ $sellerState }}</strong> &nbsp;|&nbsp; Country: India</span><br>
                                <span>
                                    @if($companyPhone) 📞 {{ $companyPhone }} @endif
                                    @if($companyEmail) &nbsp;·&nbsp; ✉️ {{ $companyEmail }} @endif
                                </span>
                                @if($companyWebsite)
                                    <br><span>🌐 {{ $companyWebsite }}</span>
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
                            <div class="inv-num">{{ $invoiceNumber ?? ($order->invoice_number ?: ('#INV-' . $order->order_number)) }}</div>
                            <div class="inv-date">
                                Issued: {{ optional($order->placed_at)->format('d M Y') ?? $order->created_at->format('d M Y') }}
                            </div>
                            <div style="font-size: 0.8rem; color: var(--muted); margin-bottom: 6px;">
                                Place of Supply: <strong>{{ $customerState }} ({{ $isIntraState ? 'Intra-State' : 'Inter-State' }})</strong>
                            </div>
                            <span class="inv-status-pill" style="background: {{ $order->payment_status === 'paid' ? '#d1fae5' : '#fef3c7' }}; color: {{ $order->payment_status === 'paid' ? '#065f46' : '#92400e' }}">
                                {{ strtoupper($order->payment_status) }}
                            </span>
                        </div>
                    </div>

                    {{-- INFO GRID --}}
                    <table class="inv-info-grid">
                        <tr>
                            <td>
                                <span class="col-label">Billed To</span>
                                <p><strong>{{ $order->customer_name }}</strong></p>
                                <p style="color: var(--muted); font-size: 0.85rem;">{{ $order->customer_email }}</p>
                                <p style="color: var(--muted); font-size: 0.85rem;">{{ $order->customer_phone }}</p>
                                <p style="color: var(--muted); font-size: 0.85rem;">State: {{ $customerState }}</p>
                            </td>
                            <td>
                                <span class="col-label">Shipped To</span>
                                <p><strong>{{ $order->shipping_name }}</strong></p>
                                <p style="color: var(--muted); font-size: 0.85rem;">{{ $order->shipping_address_line_1 }}</p>
                                @if($order->shipping_address_line_2)
                                    <p style="color: var(--muted); font-size: 0.85rem;">{{ $order->shipping_address_line_2 }}</p>
                                @endif
                                <p style="color: var(--muted); font-size: 0.85rem;">{{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}</p>
                            </td>
                            <td>
                                <span class="col-label">Payment & Order</span>
                                <p>Method: <strong>{{ strtoupper($order->payment_method) }}</strong></p>
                                <p>Order ID: <strong>#{{ $order->order_number }}</strong></p>
                                <p>Supply Type: <strong>{{ $isIntraState ? 'CGST + SGST (Intra-State)' : 'IGST (Inter-State)' }}</strong></p>
                                <p>Status: <strong style="color: {{ $order->payment_status === 'paid' ? '#10b981' : '#f59e0b' }}">{{ strtoupper($order->payment_status) }}</strong></p>
                            </td>
                        </tr>
                    </table>

                    {{-- ITEMS TABLE WITH HSN CODE --}}
                    <div class="inv-table-wrap">
                        <table class="inv-table">
                            <thead>
                                <tr>
                                    <th style="width: 5%; text-align: center;">#</th>
                                    <th style="width: 38%;">Product Description</th>
                                    <th style="width: 12%; text-align: center;">HSN/SAC</th>
                                    <th style="text-align: center; width: 8%;">Qty</th>
                                    <th style="text-align: right; width: 12%;">Price (₹)</th>
                                    <th style="text-align: right; width: 10%;">GST</th>
                                    <th style="text-align: right; width: 15%;">Total (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $idx => $item)
                                @php
                                    $vName = $item->item_snapshot['variant_name'] ?? ($item->productVariant?->name ?? null);
                                    $hsn = $item->item_snapshot['hsn_code'] ?? ($item->product?->hsn_code ?? '210690');
                                    $product = $item->product;
                                    
                                    $taxRate = (float) ($item->tax_percent ?? ($product?->taxRate?->rate ?? 0));
                                    $gstAmt = (float) ($item->tax_amount ?: (($item->unit_price * $item->quantity * $taxRate) / 100));
                                    $lineTotalWithFullTax = ((float) $item->unit_price * (int) $item->quantity) + $gstAmt;

                                    $specs = [];
                                    if ($product) {
                                        if ($product->flavor || $product->flavour) $specs[] = 'Flavor: ' . ($product->flavor ?? $product->flavour);
                                        if ($product->pack_size) $specs[] = 'Pack: ' . $product->pack_size;
                                        if ($product->age_group) $specs[] = 'Age: ' . $product->age_group;
                                    }
                                @endphp
                                <tr>
                                    <td style="text-align: center; color: var(--muted); font-size: 0.85rem;">{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="inv-prod-name">{{ $item->product_name }}</div>
                                        @if($vName) <div style="font-size: 0.8rem; color: var(--muted);">{{ $vName }}</div> @endif
                                        @foreach($specs as $spec)
                                            <span class="inv-spec-pill">{{ $spec }}</span>
                                        @endforeach
                                    </td>
                                    <td style="text-align: center; font-family: monospace; font-size: 0.85rem; color: #4b5563;">
                                        {{ $hsn }}
                                    </td>
                                    <td style="text-align: center; font-weight: 700;">{{ $item->quantity }}</td>
                                    <td style="text-align: right;">₹{{ number_format($item->unit_price, 2) }}</td>
                                    <td style="text-align: right;">
                                        <div style="font-weight: 600;">{{ number_format($taxRate, 0) }}%</div>
                                        <div style="font-size: 0.75rem; color: var(--muted);">₹{{ number_format($gstAmt, 2) }}</div>
                                    </td>
                                    <td style="text-align: right;" class="inv-amount">₹{{ number_format($lineTotalWithFullTax, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- GST TAX BREAKDOWN TABLE --}}
                <div class="inv-table-wrap" style="margin-top: 24px;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: #1f2937; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                        GST Tax Breakdown (Supplier State: {{ $sellerState }} &rarr; Place of Supply: {{ $customerState }})
                    </div>
                    <table class="inv-table" style="background: #fafafa;">
                        <thead>
                            <tr style="background: #fdf2f8;">
                                <th style="width: 15%; color: #9d174d;">HSN/SAC</th>
                                <th style="width: 17%; text-align: right; color: #9d174d;">Taxable Value</th>
                                @if($isIntraState)
                                    <th style="width: 17%; text-align: right; color: #9d174d;">CGST Rate</th>
                                    <th style="width: 17%; text-align: right; color: #9d174d;">CGST Amt</th>
                                    <th style="width: 17%; text-align: right; color: #9d174d;">SGST Rate</th>
                                    <th style="width: 17%; text-align: right; color: #9d174d;">SGST Amt</th>
                                @else
                                    <th style="width: 34%; text-align: right; color: #9d174d;">IGST Rate</th>
                                    <th style="width: 34%; text-align: right; color: #9d174d;">IGST Amt</th>
                                @endif
                                <th style="width: 17%; text-align: right; color: #9d174d;">Total Tax</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($taxSummary as $sum)
                                <tr>
                                    <td style="font-family: monospace;">{{ $sum['hsn'] }}</td>
                                    <td style="text-align: right;">₹{{ number_format($sum['taxable_value'], 2) }}</td>
                                    @if($isIntraState)
                                        <td style="text-align: right;">{{ number_format($sum['rate'] / 2, 2) }}%</td>
                                        <td style="text-align: right;">₹{{ number_format($sum['tax_amount'] / 2, 2) }}</td>
                                        <td style="text-align: right;">{{ number_format($sum['rate'] / 2, 2) }}%</td>
                                        <td style="text-align: right;">₹{{ number_format($sum['tax_amount'] / 2, 2) }}</td>
                                    @else
                                        <td style="text-align: right;">{{ number_format($sum['rate'], 2) }}%</td>
                                        <td style="text-align: right;">₹{{ number_format($sum['tax_amount'], 2) }}</td>
                                    @endif
                                    <td style="text-align: right; font-weight: 600;">₹{{ number_format($sum['tax_amount'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr style="font-weight: 700; background: #f3f4f6;">
                                <td>Total</td>
                                <td style="text-align: right;">₹{{ number_format($totalTaxable, 2) }}</td>
                                @if($isIntraState)
                                    <td style="text-align: right;">-</td>
                                    <td style="text-align: right;">₹{{ number_format($totalTax / 2, 2) }}</td>
                                    <td style="text-align: right;">-</td>
                                    <td style="text-align: right;">₹{{ number_format($totalTax / 2, 2) }}</td>
                                @else
                                    <td style="text-align: right;">-</td>
                                    <td style="text-align: right;">₹{{ number_format($totalTax, 2) }}</td>
                                @endif
                                <td style="text-align: right;">₹{{ number_format($totalTax, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- TOTALS --}}
                <div style="text-align: right; margin-top: 24px;">
                    <div class="inv-totals-box">
                        @php
                            $displayCouponDiscount = (float) $order->discount_total;
                            $displayCoinDiscount = (float) $order->coin_discount;
                        @endphp
                        
                        <div class="inv-total-line">
                            <span>Taxable Subtotal</span>
                            <strong>₹{{ number_format((float) $order->subtotal, 2) }}</strong>
                        </div>

                        @if($displayCouponDiscount > 0.01)
                            <div class="inv-total-line" style="color: #ef4444; background: #fff5f5;">
                                <span>Coupon Discount {{ $order->coupon_code ? '(' . $order->coupon_code . ')' : '' }}</span>
                                <strong>- ₹{{ number_format($displayCouponDiscount, 2) }}</strong>
                            </div>
                        @endif

                        @if($displayCoinDiscount > 0.01)
                            <div class="inv-total-line" style="color: #f97316; background: #fffaf0;">
                                <span>NB Coins Discount{{ (int) $order->coins_redeemed > 0 ? ' (' . number_format((int) $order->coins_redeemed) . ' coins)' : '' }}</span>
                                <strong>- ₹{{ number_format($displayCoinDiscount, 2) }}</strong>
                            </div>
                        @endif

                        @if($isIntraState)
                            <div class="inv-total-line">
                                <span>CGST (Central Tax)</span>
                                <strong>₹{{ number_format($totalTax / 2, 2) }}</strong>
                            </div>
                            <div class="inv-total-line">
                                <span>SGST (State Tax)</span>
                                <strong>₹{{ number_format($totalTax / 2, 2) }}</strong>
                            </div>
                        @else
                            <div class="inv-total-line">
                                <span>IGST (Integrated Tax)</span>
                                <strong>₹{{ number_format($totalTax, 2) }}</strong>
                            </div>
                        @endif

                        <div class="inv-total-line">
                            <span>Shipping</span>
                            <strong>{{ (float) $order->shipping_total > 0 ? '₹' . number_format((float) $order->shipping_total, 2) : 'FREE' }}</strong>
                        </div>

                        <div class="inv-total-line grand-tot">
                            <span>Grand Total</span>
                            <strong>₹{{ number_format(round($order->grand_total)) }}</strong>
                        </div>
                    </div>
                </div>

                    {{-- FOOTER --}}
                    <div style="text-align: center; margin-top: 40px; padding-top: 30px; border-top: 1px solid #eee; font-size: 0.8rem; color: var(--muted); line-height: 1.8;">
                        <div style="font-weight: 800; color: var(--dk); font-size: 0.9rem; margin-bottom: 5px;">{{ $companyName }}</div>
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
                        @if(!empty($footerContacts))
                            <div>{{ implode(' &nbsp;·&nbsp; ', $footerContacts) }}</div>
                        @endif
                        @if(!empty($footerLegals))
                            <div style="margin-top: 6px;">
                                {{ implode(' &nbsp;·&nbsp; ', $footerLegals) }}
                            </div>
                        @endif
                        <div style="margin-top: 10px; font-size: 0.7rem;">This is a computer-generated tax invoice and does not require a physical signature.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <a href="{{ route('user.orders.invoice-download', $order) }}" 
       style="position: fixed; bottom: 30px; right: 30px; background: linear-gradient(135deg, var(--pk), var(--pu)); color: #fff; padding: 16px 32px; border-radius: 50px; font-family: 'Fredoka One', cursive; text-decoration: none; box-shadow: 0 10px 30px rgba(124, 58, 237, 0.3); display: flex; align-items: center; gap: 10px; z-index: 100;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
        Download PDF
    </a>

    @push('scripts')
        <script>
            function toggleSidebar() {
                document.getElementById('sidebar').classList.toggle('open');
                document.getElementById('overlay').classList.toggle('show');
            }
        </script>
    @endpush
@endsection
