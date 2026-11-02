@extends('layouts.main')
@section('title', 'Checkout — NutriBuddy Kids')

@section('content')
<script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    window.NB_CHECKOUT_CONFIG = {
        enabled: true,
        isGuest: @json(auth()->guest()),
        isLoggedIn: @json((bool) auth()->check()),
        selectedAddressId: @json((string)(auth()->check() && isset($savedAddresses) && $savedAddresses->first() ? $savedAddresses->first()->id : '')),
        email: @json(auth()->user()->email ?? ''),
        phone: @json(auth()->user()->phone ?? ''),
        userCoinsBalance: @json((int)(auth()->user()->coins_balance ?? 0)),
        cartPageUrl: @json(route('cart.page')),
        citiesUrlTemplate: @json(route('checkout.cities', ['stateCode' => '__STATE__'])),
        states: @json($states),
        api: {
            cartUrl: '/user/cart',
            cartUpdateTemplate: @json(route('user.cart.items.update', ['itemId' => '__ITEM__'])),
            addressesUrl: '/user/addresses',
            checkoutSummaryUrl: '/user/checkout/summary',
            placeOrderUrl: '/user/checkout/place-order',
            sendCodOtpUrl: '/user/checkout/cod/send-otp',
            csrfTokenUrl: @json(route('csrf.token')),
        },
        codOtpEnabled: @json(\App\Models\Setting::get('cod_otp_enabled', '1') === '1'),
        cashfreeMode: @json(\App\Services\Payments\CashfreePaymentService::resolveEnvironment())
    };
</script>

    @if ($errors->any())
        <div class="checkout-validation-errors">
            <strong>Please fix the following:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- HERO -->
    <section class="product-listing-hero reveal">
        <div class="product-listing-hero-inner">
            <div class="product-listing-breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span>/</span>
                <a href="{{ route('cart.page') }}">Cart</a>
                <span>/</span>
                <span>Checkout</span>
            </div>
            <span class="product-listing-hero-badge">Secure Checkout</span>
            <h1 class="product-listing-hero-title">Complete Your Order</h1>
            <p class="product-listing-hero-sub">Review your delivery details, confirm payment, and place your NutriBuddy order securely.</p>
        </div>
    </section>
    <!-- Menu Overlay -->
    <div class="menu-overlay" id="menuOverlay"></div>

    <!-- Mobile Dropdown Menu -->
    <div class="mobile-menu" id="mobileMenu" aria-hidden="true">
        <ul>
            <li><a href="#"><span class="link-emoji"></span> Home</a></li>
            <li><a href="#"><span class="link-emoji"></span> About Us</a></li>
            <li><a href="#"><span class="link-emoji"></span> Products</a></li>
            <li><a href="#"><span class="link-emoji"></span> Personalized Diet Chart</a></li>
            <li><a href="#"><span class="link-emoji"></span> Testimonials</a></li>
        </ul>
        <div class="mobile-cta-wrap">
            <a href="#">Contact Us</a>
        </div>
    </div>

    <!-- ══ TOPBAR ══ -->
    <header class="checkout-topbar">

        <div class="topbar-steps">
            <div class="ts-arrow">›</div>
            <div class="ts active" id="step-addr">
                <div class="ts-num">1</div> Delivery Address
            </div>
            <div class="ts-arrow">›</div>
            <div class="ts" id="step-pay">
                <div class="ts-num">2</div> Payment
            </div>
        </div>
    </header>
    <div class="progress-strip">
        <div class="progress-fill" id="progressFill"></div>
    </div>

    <!-- ══ MAIN ══ -->
    <main class="checkout-main">

        <!-- ══ LEFT ══ -->
        <div class="left-panel">

            <!-- STEP 1: ADDRESS -->
            <div class="co-card active-card" id="addressCard">
                <div class="card-head">
                    <div class="card-head-left">
                        <div class="step-badge" id="addrBadge">1</div>
                        <h3>Delivery Address</h3>
                    </div>
                    @auth
                    <span style="font-size:.78rem;color:var(--text-light)" id="savedAddrCount">
                        {{ $savedAddresses->count() }} saved {{ \Illuminate\Support\Str::plural('address', $savedAddresses->count()) }}
                    </span>
                    @endauth
                </div>
                <div class="card-body">

                    @auth
                    @if($savedAddresses->count() > 0)
                    {{-- ── TABS ── --}}
                    <div class="addr-tabs">
                        <button class="addr-tab active" id="tabSaved" onclick="switchAddrTab('saved')">📍 Saved Addresses</button>
                        <button class="addr-tab" id="tabNew" onclick="switchAddrTab('new')">➕ Add New</button>
                    </div>

                    {{-- ── SAVED ADDRESS LIST ── --}}
                    <div id="savedAddrPanel">
                        <div class="saved-addresses" id="savedAddressList">
                            @foreach($savedAddresses as $addr)
                            <div class="addr-item {{ $loop->first ? 'selected' : '' }}"
                                 data-address-id="{{ $addr->id }}"
                                 onclick="selectSavedAddress(this, '{{ $addr->id }}')">
                                <div class="addr-radio"></div>
                                <div class="addr-info" style="flex:1">
                                    <div class="addr-name">
                                        {{ $addr->full_name }}
                                        <span class="addr-type-tag">{{ $addr->label ?? 'Home' }}</span>
                                    </div>
                                    <div class="addr-line">
                                        {{ $addr->address_line_1 }}{{ $addr->address_line_2 ? ', '.$addr->address_line_2 : '' }}{{ $addr->landmark ? ', Near '.$addr->landmark : '' }}
                                    </div>
                                    <div class="addr-line">
                                        {{ $addr->city }}, {{ $addr->state }} — {{ $addr->postal_code }}
                                    </div>
                                    <div class="addr-phone">📱 {{ $addr->phone }}</div>
                                </div>
                                <button class="addr-del-btn" title="Delete"
                                    onclick="deleteAddress(event, {{ $addr->id }}, this)">🗑</button>
                            </div>
                            @endforeach
                        </div>
                        <button class="add-addr-btn" onclick="switchAddrTab('new')" style="margin-top:8px">
                            <span>➕</span> Add a New Address
                        </button>
                    </div>
                    @endif
                    @endauth

                    {{-- ── NEW ADDRESS FORM ── --}}
                    <div id="newAddrPanel" @auth @if($savedAddresses->count() > 0) style="display:none" @endif @endauth>
                        <div id="addressFormPanel">
                            <div class="new-addr-form show">
                                <div class="form-grid">
                                    <div class="form-group"><label>First Name *</label><input type="text" id="firstName" placeholder="e.g. Priya"></div>
                                    <div class="form-group"><label>Last Name *</label><input type="text" id="lastName" placeholder="e.g. Sharma"></div>
                                </div>
                                <div class="form-grid single" style="margin-top:14px">
                                    <div class="form-group"><label>Mobile Number *</label><input type="tel" id="addressPhone" placeholder="+91 XXXXX XXXXX"></div>
                                </div>
                                <div class="form-grid single" style="margin-top:14px">
                                    <div class="form-group"><label>Flat / House / Apartment *</label><input type="text" id="addressLine1" placeholder="e.g. 42, Sunshine Residency"></div>
                                </div>
                                <div class="form-grid single" style="margin-top:14px">
                                    <div class="form-group"><label>Street / Area / Colony *</label><input type="text" id="addressLine2" placeholder="e.g. HSR Layout, Sector 3"></div>
                                </div>
                                <div class="form-grid" style="margin-top:14px">
                                    <div class="form-group">
                                        <label>State *</label>
                                        <div class="checkout-combobox">
                                            <input type="text" id="stateField" placeholder="Type or select state" autocomplete="off" data-old-state="{{ old('state') }}">
                                            <input type="hidden" id="stateCodeField">
                                            <div class="checkout-combobox-menu" id="stateDropdown" hidden></div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>City *</label>
                                        <div class="checkout-combobox checkout-city-select-wrap">
                                            <input type="text" id="cityField" placeholder="Select state first" autocomplete="off" data-old-city="{{ old('city') }}" disabled>
                                            <div class="checkout-combobox-menu" id="cityDropdown" hidden></div>
                                            <span class="checkout-city-spinner" id="citySpinner" aria-hidden="true"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-grid" style="margin-top:14px">
                                    <div class="form-group"><label>Pincode *</label><input type="text" maxlength="6" placeholder="6-digit pincode" id="newPincode" oninput="autoFillCity()"></div>
                                    <div class="form-group">
                                        <label>Address Type</label>
                                        <div class="addr-type-row">
                                            <button class="addr-type-btn active" data-type="Home" onclick="toggleAddrType(this)">🏠 Home</button>
                                            <button class="addr-type-btn" data-type="Work" onclick="toggleAddrType(this)">💼 Work</button>
                                            <button class="addr-type-btn" data-type="Other" onclick="toggleAddrType(this)">📍 Other</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button class="continue-btn" id="addressContinueBtn" onclick="saveAndGoToPayment()">
                        Continue to Payment
                    </button>
                </div>
            </div>

            <!-- STEP 2: PAYMENT -->
            <div class="co-card" id="paymentCard" style="opacity:.5;pointer-events:none">
                <div class="card-head">
                    <div class="card-head-left">
                        <div class="step-badge" id="payBadge">2</div>
                        <h3>Payment Method</h3>
                    </div>
                    <span style="font-size:.78rem;color:var(--text-light)">Choose how to pay</span>
                </div>
                <div class="card-body">
                    @php
                        $cashfreeEnabled = \App\Models\Setting::get('cashfree_enabled', config('services.cashfree.app_id') ? '1' : '0') === '1';
                        $razorpayEnabled = \App\Models\Setting::get('razorpay_enabled', config('services.razorpay.key_id') ? '1' : '0') === '1';
                        $codEnabled      = \App\Models\Setting::get('cod_enabled', '1') === '1';

                        $defaultMethod = 'cashfree';
                        if (!$cashfreeEnabled) {
                            if ($razorpayEnabled) {
                                $defaultMethod = 'razorpay';
                            } elseif ($codEnabled) {
                                $defaultMethod = 'cod';
                            }
                        }
                    @endphp
                    
                    <div class="payment-methods">
                        @if ($cashfreeEnabled)
                            <div class="pay-method {{ $defaultMethod === 'cashfree' ? 'selected' : '' }}" id="payMethodCashfree" data-method="cashfree"
                                onclick="selectPayMethod(this,'cashfree')">
                                <div class="pay-head">
                                    <div class="pay-radio"></div>
                                    <div class="pay-icon">💵</div>
                                    <div>
                                        <div class="pay-name">Cashfree Online Payment</div>
                                        <div class="pay-sub">Pay securely with UPI, cards, wallets or netbanking</div>
                                    </div>
                                    <div class="pay-tags"></div>
                                </div>
                            </div>
                        @endif

                        @if ($razorpayEnabled)
                            <div class="pay-method {{ $defaultMethod === 'razorpay' ? 'selected' : '' }}" id="payMethodRazorpay" data-method="razorpay"
                                onclick="selectPayMethod(this,'razorpay')">
                                <div class="pay-head">
                                    <div class="pay-radio"></div>
                                    <div class="pay-icon">💳</div>
                                    <div>
                                        <div class="pay-name">Razorpay Secure</div>
                                        <div class="pay-sub">Pay with UPI, Cards, Netbanking or Wallets</div>
                                    </div>
                                    <div class="pay-tags"></div>
                                </div>
                            </div>
                        @endif

                        @if ($codEnabled)
                            <div class="pay-method {{ $defaultMethod === 'cod' ? 'selected' : '' }}" id="payMethodCod" data-method="cod"
                                onclick="selectPayMethod(this,'cod')">
                                <div class="pay-head">
                                    <div class="pay-radio"></div>
                                    <div class="pay-icon">🤝</div>
                                    <div>
                                        <div class="pay-name">Cash on Delivery (COD)</div>
                                        <div class="pay-sub">Pay in cash upon delivery</div>
                                    </div>
                                    <div class="pay-tags"></div>
                                </div>
                            </div>
                        @endif

                        @if (!$cashfreeEnabled && !$razorpayEnabled && !$codEnabled)
                            <div class="text-danger p-3 text-center w-100" style="font-weight: 800; font-family: 'Nunito', sans-serif;">
                                ⚠️ No payment methods are currently active.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div><!-- /left-panel -->

        <!-- ══ RIGHT: ORDER SUMMARY ══ -->
        <div class="order-summary">
            <div class="os-head">
                <h3>Order Summary</h3>
                <div class="item-count">3 Items</div>
            </div>
            <div class="os-body">
                <div class="cart-items" id="checkoutCartItems"></div>

                <!-- Coupon -->
                <div class="coupon-row" id="couponRow">
                    <div class="coupon-label">Coupon / Promo Code</div>
                    <div class="coupon-input-row">
                        <input class="coupon-input" type="text" placeholder="Enter coupon code" id="couponInput">
                        <button class="coupon-apply-btn" onclick="applyCoupon()">Apply</button>
                    </div>
                    <div class="coupon-applied-msg" id="couponMsg"></div>
                </div>

                <!-- Loyalty Coins — Auto Applied -->
                @auth
                @php
                    $userCoinBalance = (int) auth()->user()->coins_balance;
                @endphp
                @if ($userCoinBalance > 0)
                <div class="loyalty-row" id="coinRedeemRow" style="margin-top: 20px; border-top: 2px dashed var(--border); padding-top: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <div style="font-family: 'Nunito', sans-serif; font-weight: 800; font-size: 0.82rem; color: var(--dk);">NB Coins 🪙</div>
                        <div id="userCoinBalance" data-balance="{{ $userCoinBalance }}" style="font-family: 'Fredoka One', cursive; color: var(--or); font-size: 0.85rem;">{{ $userCoinBalance }} Available</div>
                    </div>
                    <div id="coinRedeemBox" style="background: #fff8f0; border: 1.5px solid #f5c18a; border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 1.1rem;">🪙</span>
                            <div>
                                <div id="coinsToRedeemValue" style="font-family: 'Fredoka One', cursive; color: var(--or); font-size: 0.9rem; line-height: 1.2;">Calculating...</div>
                                <div style="font-size: 0.7rem; color: #888; margin-top: 2px;">Applied automatically</div>
                            </div>
                        </div>
                        <div id="coinDiscountText" style="font-family: 'Nunito', sans-serif; font-weight: 800; font-size: 0.88rem; color: #00a870;">−₹0</div>
                    </div>
                </div>
                @endif
                @endauth


                <!-- Price Breakdown -->
                <div class="price-breakdown" id="priceBreakdown">
                    <div class="pb-row">
                        <span class="pb-label" id="pbMrpLabel">Price (0 items)</span>
                        <span class="pb-val" id="pbMrpValue">₹0</span>
                    </div>
                    <div class="pb-row">
                        <span class="pb-label">Product Discount</span>
                        <span class="pb-val green" id="pbProductDiscount">− ₹0</span>
                    </div>
                    <div class="pb-row">
                        <span class="pb-label">Delivery Charges</span>
                        <span class="pb-val green" id="pbDelivery">FREE 🎉</span>
                    </div>
                    <div class="pb-row">
                        <span class="pb-label">GST (Applied on top)</span>
                        <span class="pb-val" id="pbGst">₹0</span>
                    </div>
                    <div class="pb-row" id="couponRow2" style="display:none">
                        <span class="pb-label">Coupon</span>
                        <span class="pb-val green" id="couponDiscount">− ₹0</span>
                    </div>
                    <div class="pb-row" id="coinDiscountRow" style="display:none">
                        <span class="pb-label">NB Coins Discount</span>
                        <span class="pb-val green" id="coinDiscountVal">− ₹0</span>
                    </div>
                    <div class="pb-divider"></div>
                    <div class="pb-total-row">
                        <div>
                            <div class="pb-total-label">Total Amount</div>
                            <div class="tax-note">Incl. all taxes</div>
                        </div>
                        <div>
                            <div class="pb-total-val" id="totalDisplay">₹0</div>
                        </div>
                    </div>
                </div>
                <!-- Place Order (shown after payment section is active) -->
                <div id="placeOrderWrap" style="display:none;margin-top:16px">
                    <button class="place-order-btn" onclick="openOtpModal()">
                        🔒 Place Order Securely — ₹0
                    </button>
                    <div class="trust-row">
                        <div class="tr-badge">🔒 SSL Secure</div>
                        <div class="tr-badge">✅ FSSAI Certified</div>
                        <div class="tr-badge">🔄 Easy Returns</div>
                    </div>
                </div>

            </div>
        </div>

    </main>

    <!-- ══════════════════════════════
         COD OTP VERIFICATION MODAL
    ══════════════════════════════ -->
    <div class="cod-otp-overlay" id="codOtpOverlay" style="display: none;">
        <div class="cod-otp-backdrop" onclick="closeCodOtpModal()"></div>
        <div class="cod-otp-card">
            <button type="button" class="cod-otp-close" onclick="closeCodOtpModal()" aria-label="Close">&times;</button>
            <div class="cod-otp-icon">📦</div>
            <h3 class="cod-otp-title">Confirm Cash on Delivery</h3>
            <p class="cod-otp-subtitle">
                Please enter the 6-digit verification code sent to <strong id="codOtpMaskedPhone">your phone</strong> to confirm your order.
            </p>
            
            <div class="cod-otp-input-wrap">
                <input type="text" id="codOtpInput" class="cod-otp-input" maxlength="6" inputmode="numeric" pattern="[0-9]*" placeholder="• • • • • •" autocomplete="one-time-code">
            </div>
            <div class="cod-otp-error" id="codOtpError" style="display: none;"></div>

            <button type="button" class="cod-otp-submit-btn" id="codOtpSubmitBtn" onclick="submitCodOtp()">
                Verify & Place Order
            </button>

            <div class="cod-otp-resend-wrap">
                <span id="codOtpResendTimer">Resend code in <strong id="codOtpCountdown">30</strong>s</span>
                <button type="button" id="codOtpResendBtn" class="cod-otp-resend-btn" style="display: none;" onclick="resendCodOtp()">Resend Code</button>
            </div>
        </div>
    </div>

    <style>
        .cod-otp-overlay {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .cod-otp-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(6, 60, 54, 0.45);
            backdrop-filter: blur(4px);
        }
        .cod-otp-card {
            position: relative;
            background: #ffffff;
            border-radius: 20px;
            max-width: 420px;
            width: 100%;
            padding: 32px 28px;
            text-align: center;
            box-shadow: 0 20px 45px rgba(6, 60, 54, 0.18);
            border: 1px solid rgba(6, 60, 54, 0.08);
            animation: codModalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            z-index: 1;
        }
        @keyframes codModalPop {
            from { opacity: 0; transform: scale(0.92) translateY(8px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .cod-otp-close {
            position: absolute;
            top: 16px;
            right: 16px;
            background: #f1f5f9;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 20px;
            line-height: 1;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s, color 0.15s;
        }
        .cod-otp-close:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .cod-otp-icon {
            font-size: 40px;
            margin-bottom: 12px;
        }
        .cod-otp-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: #063c36;
            margin-bottom: 8px;
            letter-spacing: -0.3px;
        }
        .cod-otp-subtitle {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 24px;
            line-height: 1.45;
        }
        .cod-otp-subtitle strong {
            color: #063c36;
        }
        .cod-otp-input-wrap {
            margin-bottom: 12px;
        }
        .cod-otp-input {
            width: 100%;
            height: 52px;
            text-align: center;
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: 12px;
            color: #063c36;
            background: #f8fafc;
            border: 2px solid #cbd5e1;
            border-radius: 12px;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
            box-sizing: border-box;
            padding-left: 12px;
        }
        .cod-otp-input:focus {
            border-color: #00b37e;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(0, 179, 126, 0.15);
        }
        .cod-otp-error {
            color: #dc2626;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 14px;
            line-height: 1.35;
        }
        .cod-otp-submit-btn {
            width: 100%;
            height: 48px;
            background: linear-gradient(135deg, #00b37e, #047857);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(0, 179, 126, 0.28);
            transition: transform 0.15s, box-shadow 0.15s, opacity 0.15s;
        }
        .cod-otp-submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(0, 179, 126, 0.35);
        }
        .cod-otp-submit-btn:disabled {
            opacity: 0.65;
            cursor: not-allowed;
            transform: none;
        }
        .cod-otp-resend-wrap {
            margin-top: 16px;
            font-size: 0.85rem;
            color: #64748b;
        }
        .cod-otp-resend-btn {
            background: none;
            border: none;
            color: #00b37e;
            font-weight: 700;
            cursor: pointer;
            padding: 0;
            text-decoration: underline;
        }
        .cod-otp-resend-btn:hover {
            color: #047857;
        }
    </style>

    <!-- ══════════════════════════════
                     PHONE / OTP MODAL
                ══════════════════════════════ -->
    <div class="success-overlay" id="successOverlay">
        <div class="success-box">
            <span class="success-icon">🎉</span>
            <h2>Order Placed!</h2>
            <p>Yay! Your NutriBuddy order has been placed successfully. Your little superheroes will receive their gummies soon!</p>
            <div class="order-id-box" id="successOrderNumber">Order ID: —</div>
            <p style="font-size:.82rem;color:var(--text-light);margin-bottom:20px">Estimated delivery: <strong>2–5 business days</strong><br>You'll receive an SMS confirmation shortly.</p>
            <div class="success-btns">
                <a class="btn-details" id="orderDetailBtn" href="#">📋 View Order Details</a>
                <a class="btn-home" href="{{ route('home') }}">🏠 Back to Home</a>
            </div>
            <div class="redirect-countdown" id="redirectCountdownWrap">
                Auto-redirecting to your order in <strong id="redirectCountdown">5</strong>s…
            </div>
        </div>
    </div>

    <!-- footer -->
    <!-- ══════════════════════════════════════════
                       NEWSLETTER
                  ══════════════════════════════════════════ -->
    <!-- ══════════════════════════════════════════
                       FOOTER
                  ══════════════════════════════════════════ -->@endsection
