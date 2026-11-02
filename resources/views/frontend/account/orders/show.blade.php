
@extends("layouts.user-panel")
@section("title", "Order Details NutriBuddy Kids")
@section("panel-page-class", "panel-order")
@section("panel-content")
    <div class="inner-topbar">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <line x1="3" y1="6" x2="21" y2="6" />
                <line x1="3" y1="12" x2="21" y2="12" />
                <line x1="3" y1="18" x2="21" y2="18" />
            </svg>
        </button>
        <span class="it-title">Order Details ??</span>
        <div class="sidebar-spacer"></div>
    </div>

    <div class="page">
        <div class="orders-card fade-in d1 order-detail-card mb-24">
            <div class="order-detail-head mb-20">
                <div>
                    <h2>Order {{ $order->order_number }}</h2>
                    <p>Placed at: {{ optional($order->placed_at)->format("d M Y h:i A") ?? "-" }}</p>
                </div>
                <div class="order-detail-total">{{ number_format($order->grand_total, 2) }}</div>
            </div>

            <!-- ORDER TRACKING SYSTEM -->
            @php
                $step1 = in_array($order->status, ["pending", "confirmed", "processing", "packed", "shipped", "delivered", "returned"]);
                $step2 = in_array($order->status, ["processing", "packed", "shipped", "delivered", "returned"]);
                $step3 = in_array($order->status, ["shipped", "delivered", "returned"]);
                $step4 = in_array($order->status, ["delivered", "returned"]);
                $isCancelled = $order->status === "cancelled";
                $activeReturnStatuses = ["pending", "approved", "completed"];
                $returnableItems = $order->items->map(function ($item) use ($activeReturnStatuses) {
                    $alreadyRequested = $item->returnItems
                        ->filter(fn ($returnItem) => in_array($returnItem->returnRequest?->status, $activeReturnStatuses, true))
                        ->sum("quantity");

                    $item->setAttribute("returned_quantity", (int) $alreadyRequested);
                    $item->setAttribute("returnable_quantity", max(0, (int) $item->quantity - (int) $alreadyRequested));

                    return $item;
                })->filter(fn ($item) => (int) $item->returnable_quantity > 0);
            @endphp

            @if(!$isCancelled)
            <div class="stepper-progress sp-container">
                <!-- Step 1 -->
                <div class="sp-step flex-1 text-center {{ $step1 ? "done" : "" }}">
                    <div class="sp-ball sp-ball-center">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
                    </div>
                    <div class="sp-label sp-label-margin">Order Placed</div>
                </div>
                <div class="sp-line flex-1"><div class="sp-line-fill {{ $step2 ? "done" : "" }}"></div></div>

                <!-- Step 2 -->
                <div class="sp-step flex-1 text-center {{ $step2 ? "done" : "" }}">
                    <div class="sp-ball sp-ball-center">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    </div>
                    <div class="sp-label sp-label-margin">Processing</div>
                </div>
                <div class="sp-line flex-1"><div class="sp-line-fill {{ $step3 ? "done" : "" }}"></div></div>

                <!-- Step 3 -->
                <div class="sp-step flex-1 text-center {{ $step3 ? "done" : "" }}">
                    <div class="sp-ball sp-ball-center">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17h4V5H2v12h3"/><path d="M20 17h2v-3.34a4 4 0 0 0-1.17-2.83L19 9h-5"/><path d="M14 17h1"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                    </div>
                    <div class="sp-label sp-label-margin">Shipped</div>
                </div>
                <div class="sp-line flex-1"><div class="sp-line-fill {{ $step4 ? "done" : "" }}"></div></div>

                <!-- Step 4 -->
                <div class="sp-step flex-1 text-center {{ $step4 ? "done" : "" }}">
                    <div class="sp-ball sp-ball-center">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                    </div>
                    <div class="sp-label sp-label-margin">Delivered</div>
                </div>
            </div>
            @else
            <div class="order-cancelled-notice">
                <h4 class="m-0">Order Cancelled</h4>
                <p class="cancelled-notice-text">This order has been cancelled and will not be delivered.</p>
            </div>
            @endif

            <div class="order-detail-meta order-meta-container">
                <span class="status-badge {{ $order->status === "delivered" ? "s-delivered" : ($order->status === "cancelled" ? "s-cancelled" : "s-pending") }}">
                    {{ strtoupper($order->status) }}
                </span>
                <span class="order-pill">{{ strtoupper($order->payment_method ?? "cod") }}</span>
                
                @if($order->status === "delivered" && $returnableItems->isNotEmpty())
                    <button class="btn-return-items" onclick="openOrderDetailReturnModal()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 5.5 5.5a5.5 5.5 0 0 1-5.5 5.5H11"/></svg>
                        Return Items
                    </button>
                @elseif($order->returns()->exists())
                    <span class="return-status-badge">
                        RETURN STATUS: {{ strtoupper($order->returns()->latest()->first()->status) }}
                    </span>
                @endif
            </div>
        </div>

        <!-- FULL DETAILS (ADDRESS & PAYMENT) -->
        <div class="grid-layout-cards">
            <!-- Shipping Details -->
            <div class="orders-card fade-in d2 order-detail-card mb-0">
                <h3 class="card-title order-card-title">Shipping Address</h3>
                <div class="order-card-text">
                    <strong class="order-card-strong">{{ $order->shipping_name }}</strong>
                    {{ $order->shipping_address_line_1 }}<br>
                    @if($order->shipping_address_line_2){{ $order->shipping_address_line_2 }}<br>@endif
                    @if($order->shipping_landmark)Landmark: {{ $order->shipping_landmark }}<br>@endif
                    {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}<br>
                    {{ $order->shipping_country }}<br>
                    <strong class="inline-block mt-8">Phone:</strong> {{ $order->shipping_phone }}
                </div>
            </div>

            <!-- Payment Details -->
            <div class="orders-card fade-in d2 order-detail-card mb-0">
                <h3 class="card-title order-card-title">Payment Summary</h3>
                <div class="payment-summary-text">
                    @php
                        $couponDiscount = (float) $order->discount_total;
                        $coinDiscount = (float) $order->coin_discount;
                        $orderPayment = $order->payments->firstWhere("status", "paid")
                            ?? $order->payments->sortByDesc("created_at")->first();
                        $orderPaymentId = $orderPayment?->gateway_payment_id ?: $orderPayment?->gateway_order_id;
                    @endphp

                    <div class="flex-between">
                        <span>Subtotal:</span>
                        <span>{{ number_format((float) $order->subtotal, 2) }}</span>
                    </div>
                    
                    @if($couponDiscount > 0)
                    <div class="flex-between text-mn">
                        <span>Coupon Discount:</span>
                        <span>{{ number_format($couponDiscount, 2) }}</span>
                    </div>
                    @endif

                    @if($coinDiscount > 0)
                    <div class="flex-between text-or">
                        <span>NB Coins Discount{{ (int) $order->coins_redeemed > 0 ? " (" . number_format((int) $order->coins_redeemed) . " coins)" : "" }}:</span>
                        <span>{{ number_format($coinDiscount, 2) }}</span>
                    </div>
                    @endif

                    <div class="flex-between">
                        <span>Tax (GST):</span>
                        <span>{{ number_format($order->tax_total, 2) }}</span>
                    </div>
                    <div class="flex-between">
                        <span>Shipping:</span>
                        <span>{{ number_format($order->shipping_total, 2) }}</span>
                    </div>
                    <hr class="dashed-hr">
                    <div class="flex-between total-row">
                        <span>Grand Total:</span>
                        <span>{{ number_format($order->grand_total, 2) }}</span>
                    </div>
                    <div class="payment-method-box">
                        <strong>Method:</strong> {{ strtoupper($order->payment_method) }}<br>
                        @if($orderPaymentId)
                            <strong>Payment ID:</strong> <span style="overflow-wrap:anywhere;">{{ $orderPaymentId }}</span><br>
                        @endif
                        <strong>Status:</strong> <span class="{{ $order->payment_status === "paid" ? "text-success" : "text-danger" }}">{{ strtoupper($order->payment_status) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="orders-card fade-in d2 order-detail-card">
            <h3 class="card-title">Items</h3>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Tax</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->items as $item)
                        <tr>
                            <td>
                                <div class="item-product-col">
                                    <div class="font-medium">{{ $item->product_name }}</div>
                                    @php
                                        $vName = $item->item_snapshot["variant_name"] ?? ($item->productVariant?->name ?? null);
                                        $variant = $item->productVariant;
                                        $product = $item->product;
                                    @endphp
                                    @if($vName)
                                        <div class="item-variant-name">{{ $vName }}</div>
                                    @endif
                                    <div class="item-specs-wrap">
                                    @php
                                        $specs = collect();
                                        // 1. First set the product-level ones (They take priority for labels)
                                        if ($product) {
                                            if ($product->flavor || $product->flavour) {
                                                $specs->put("flavor", ["k" => "Flavor", "v" => $product->flavor ?? $product->flavour]);
                                            }
                                            if ($product->pack_size) {
                                                $specs->put("pack", ["k" => "Pack Size", "v" => $product->pack_size]);
                                            }
                                            if ($product->age_group) {
                                                $specs->put("age", ["k" => "Age Group", "v" => $product->age_group]);
                                            }
                                        }

                                        // 2. Add other variant attributes ONLY if they are not the above
                                        $vAttributes = $variant?->attributes ?? $item->item_snapshot["variant_attributes"] ?? null;
                                        if ($vAttributes) {
                                            foreach($vAttributes as $k => $v) {
                                                $key = strtolower(str_replace(["_", "-"], " ", $k));
                                                // If it is a common key we already handled, skip it to avoid duplicates
                                                if (str_contains($key, "flav") || str_contains($key, "pack") || str_contains($key, "age")) {
                                                    continue;
                                                }
                                                $specs->put($key, ["k" => ucfirst($k), "v" => $v]);
                                            }
                                        }
                                    @endphp
                                    @foreach($specs as $spec)
                                        <span>{{ $spec["k"] }}: {{ $spec["v"] }}</span>
                                    @endforeach
                                    </div>
                                </div>
                            </td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format($item->unit_price, 2) }}</td>
                            <td>{{ number_format($item->tax_amount, 2) }}</td>
                            <td>{{ number_format((float) $item->line_total + (float) $item->tax_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No items found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="orders-card fade-in d3 order-detail-card">
            <h3 class="card-title">Order Timeline</h3>
            <div class="order-timeline">
            @php
                if (!function_exists("getStatusNarration")) {
                    function getStatusNarration($status) {
                        return match(strtolower($status)) {
                            "pending" => "We have received your order and are waiting to process it.",
                            "confirmed" => "Your order has been confirmed and is in our system.",
                            "processing" => "We are currently processing and preparing your items.",
                            "packed" => "Your items have been securely packed and are awaiting courier pickup.",
                            "shipped" => "Your order has been handed over to our delivery partner and is on its way.",
                            "delivered" => "Your order has been successfully delivered. We hope you enjoy it!",
                            "cancelled" => "Your order has been cancelled.",
                            "returned" => "A return has been processed for your order.",
                            default => "Your order status was updated to " . ucfirst($status) . "."
                        };
                    }
                }
            @endphp

            @forelse($order->statusHistories as $history)
                <div class="timeline-item timeline-item-spaced">
                    <div class="timeline-time timeline-time-styled">
                        {{ optional($history->created_at)->format("d M Y, h:i A") }}
                    </div>
                    <div class="timeline-content">
                        <strong class="timeline-status-strong">
                            {{ ucfirst($history->to_status) }}
                        </strong>
                        <p class="timeline-narration">
                            {{ getStatusNarration($history->to_status) }}
                        </p>
                        @if($history->note)
                            <div class="timeline-note-box">
                                <strong>Note:</strong> {{ $history->note }}
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="timeline-empty timeline-empty-styled">No status timeline available.</p>
            @endforelse
            </div>
        </div>
    </div>

    <div id="orderDetailReturnModalOverlay" class="nb-ret-overlay d-none">
        <div class="nb-ret-modal">
            <div class="nb-ret-modal-header">
                <h3>Request Return ??</h3>
                <button type="button" class="nb-ret-close" onclick="closeOrderDetailReturnModal()">&times;</button>
            </div>
            <div class="nb-ret-modal-body">
                <form id="orderDetailReturnForm" action="{{ route("user.orders.returns.store", $order) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-15">
                        <label class="nb-ret-file-label label-lg">Select Items & Quantity</label>
                        <div class="nb-ret-items-wrap mt-10">
                            @foreach($returnableItems as $item)
                                <div data-return-line class="nb-ret-item-row">
                                    <div class="nb-ret-item-details">
                                        <div class="nb-ret-item-title">{{ $item->product_name }}</div>
                                        <div class="nb-ret-item-meta">
                                            Ordered: {{ $item->quantity }} | Already requested: {{ $item->returned_quantity }} | Available: {{ $item->returnable_quantity }}
                                        </div>
                                    </div>
                                    <div>
                                        <input data-return-hidden type="hidden" name="items[{{ $loop->index }}][order_item_id]" value="{{ $item->id }}">
                                        <input data-return-qty type="number" class="nb-ret-qty-input" name="items[{{ $loop->index }}][quantity]" min="0" max="{{ $item->returnable_quantity }}" value="{{ old("items." . $loop->index . ".quantity", 0) }}" aria-label="Return quantity for {{ $item->product_name }}">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div id="returnQuantityError" class="error-msg-styled">Please enter quantity for at least one item.</div>
                    </div>
                    
                    <div class="mb-15">
                        <label class="nb-ret-file-label">Reason for Return</label>
                        <select name="reason" class="nb-ret-input" required>
                            <option value="">Select a reason</option>
                            @foreach(\App\Support\OrderFlow::RETURN_REASONS as $reason)
                                <option value="{{ $reason }}">{{ $reason }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-15">
                        <label class="nb-ret-file-label">Additional Comments</label>
                        <textarea name="comments" rows="3" class="nb-ret-input nb-ret-textarea" placeholder="Tell us more about the issue..."></textarea>
                    </div>
                    
                    <div class="mb-20">
                        <label class="nb-ret-file-label">Upload Images/Videos (Optional, Max 10MB)</label>
                        <input type="file" name="attachments[]" accept="image/*,video/*" multiple class="nb-ret-input nb-ret-file">
                    </div>
                    
                    <div class="flex-gap-10">
                        <button type="submit" class="nb-ret-submit-btn btn-flex-1">Submit Request</button>
                        <button type="button" class="nb-ret-cancel-btn btn-flex-1" onclick="closeOrderDetailReturnModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

