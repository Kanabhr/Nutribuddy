@extends('layouts.main')
@section('title', 'Cart - NutriBuddy Kids')



@section('content')
    <section class="cart-page">
        <div class="cart-page-head">
            <div>
                <h1 style="font-family:'Fredoka One',cursive;color:var(--dk);margin:0 0 6px;">Your Cart</h1>
            </div>
        </div>

        <div class="cart-page-grid">
            <!-- Cart Items -->
            <div class="cart-inner cart-panel">
                <h4 class="title-text" style="margin:0 0 14px;">
                    Cart Items <span id="cartPageCount">0</span> 
                </h4>
                <div id="cartPageItems" class="cart-page-items-list"></div>
                <div id="cartPageEmpty"
                    style="display:none;padding:18px;border:2px dashed var(--border);border-radius:16px;color:var(--text-light);text-align:center;">
                    Your cart is empty.
                    <a href="{{ route('product') }}" style="color:var(--pk);font-weight:800;text-decoration:none;">Shop now</a>
                </div>
            </div>

            <!-- Summary -->
            <div class="cart-panel">
                <h3 style="margin:0 0 10px;font-family:'Nunito',sans-serif;font-weight:900;color:var(--dk);">Summary</h3>
                <div class="text-box cart-summary-box">
                    <h5 style="margin:0;" id="cartSummaryLabel">Total Amount</h5>
                    <span id="cartPageSubtotal">Rs. 0</span>
                </div>
                <div style="margin-top:14px;display:flex;gap:10px;flex-direction:column;">
                    <a href="{{ route('checkout.index') }}" class="nav-cta" id="cartCheckoutBtn"
                        style="text-align:center;text-decoration:none;">Checkout</a>
                    <a href="{{ route('product') }}" class="nav-cta"
                        style="border:2px solid var(--pkl);text-align:center;text-decoration:none;">Continue Shopping</a>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        @php
            $cartPageConfig = [
                'enabled' => true,
                'cartUrl' => route('user.cart.index'),
                'deleteTemplate' => route('user.cart.items.destroy', ['itemId' => '__ITEM__']),
                'updateTemplate' => route('user.cart.items.update', ['itemId' => '__ITEM__']),
                'csrf' => csrf_token(),
            ];
        @endphp
        <script id="nbCartPageConfig" type="application/json">
            {!! json_encode($cartPageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
        </script>
    @endpush
@endsection
