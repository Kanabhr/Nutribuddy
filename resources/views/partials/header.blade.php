@php
    $sideSectionLogo = \App\Models\Setting::get('side_section_logo');
    $sideSectionLogoExists = $sideSectionLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($sideSectionLogo);
    $sideSectionLogoUrl = $sideSectionLogoExists
        ? asset('storage/' . $sideSectionLogo) . '?v=' . \Illuminate\Support\Facades\Storage::disk('public')->lastModified($sideSectionLogo)
        : asset('img/logo.png');
@endphp

<nav id="mainNav">
         <button type="button" class="hamburger" id="hamburgerBtn" aria-expanded="false" aria-label="Open menu">
            <img src="{{ asset('img/menu.png') }}" alt="Menu"
                onerror="this.style.display='none';this.parentElement.innerHTML='<span></span><span></span><span></span>'">
        </button>
    <a href="{{ route('home') }}" class="logo-new">
        <img src="{{ $sideSectionLogoUrl }}" alt="NutriBuddy">
    </a>

    <ul class="nav-links">
        <li><a href="{{ route('home') }}">Home</a></li>
        <li><a href="{{ route('about') }}">About Us</a></li>
        <li><a href="{{ route('product') }}">Products</a></li>
        <li><a href="{{ route('diet_chart') }}">Personalized Diet Chart</a></li>
        <li><a href="{{ route('testimonials') }}">Testimonials</a></li>
        <li><a href="{{ route('ingredients') }}">Ingredients</a></li>
    </ul>

    <div class="nav-actions">
        <button id="cartIconBtn" title="View Cart">
            <img src="{{ asset('img/shopping-cart.png') }}" alt="Cart"
                onerror="this.style.display='none';this.parentElement.insertAdjacentHTML('afterbegin','<span style=\'font-size:1.15rem\'>🛒</span>')">
            <span class="cart-count" id="cartCount" data-guest="{{ auth()->check() ? 'false' : 'true' }}">0</span>
        </button>

        <div class="profile-dropdown">
            <button type="button" id="profileIconBtn" class="nav-icon-link profile-btn" title="Account">
                <img src="{{ asset('img/user.png') }}" alt="Profile" style="width:24px;height:24px;border-radius:50%;"
                    onerror="this.style.display='none';this.parentElement.insertAdjacentHTML('afterbegin','<span style=\'font-size:1.15rem\'>👤</span>')">
            </button>
            <div class="dropdown-content">
                @auth
                    <div class="user-info-box">
                        <span class="user-name">{{ Auth::user()->name }}</span>
                        <span class="user-role">{{ ucfirst(Auth::user()->role) }}</span>
                    </div>
                    <hr>
                    <a href="{{ route('userdashboard') }}">
                        <iconify-icon icon="solar:widget-outline"></iconify-icon> Dashboard
                    </a>
                    <a href="{{ route('personal-info') }}">
                        <iconify-icon icon="solar:user-outline"></iconify-icon> My Profile
                    </a>
                    <hr>
                    <form action="{{ route('frontend.logout') }}" method="POST" id="logoutForm">
                        @csrf
                        <a href="#" onclick="document.getElementById('logoutForm').submit();" class="logout-link">
                            <iconify-icon icon="solar:logout-outline"></iconify-icon> Logout
                        </a>
                    </form>
                @else
                    <a href="javascript:void(0)" onclick="openLoginModal()" class="login-link">
                        <iconify-icon icon="solar:login-outline"></iconify-icon> Login
                    </a>
                @endauth
            </div>
        </div>

        <a href="{{ route('contact') }}" class="nav-cta">Contact Us</a>

   
    </div>
</nav>

<style>
    /* Profile Dropdown Styles */
    .profile-dropdown {
        position: relative;
        display: inline-block;
    }

    .profile-btn {
        background: var(--cr);
        border: 2.5px solid var(--pkl);
        border-radius: 50%;
        cursor: pointer;
        padding: 0;
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        flex-shrink: 0;
    }

    .profile-btn:hover {
        background: var(--pkl);
        border-color: var(--pk);
        transform: scale(1.08);
    }

    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        background-color: #fff;
        min-width: 220px;
        box-shadow: 0px 10px 25px rgba(0,0,0,0.1);
        z-index: 1000;
        border-radius: 16px;
        padding: 12px 0;
        margin-top: 12px; /* Visual gap */
        border: 1px solid rgba(255, 107, 156, 0.1);
        animation: fadeInDown 0.3s ease;
    }

    /* Invisible bridge to prevent losing hover when moving mouse across the gap */
    .profile-dropdown::after {
        content: "";
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        height: 20px; /* Bridge height covers the 12px gap + overlap */
        display: none;
    }

    .profile-dropdown:hover::after {
        display: block;
    }

    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .profile-dropdown:hover .dropdown-content {
        display: block;
    }

    .profile-dropdown:focus-within .dropdown-content {
        display: block;
    }

    .dropdown-content a {
        color: #444;
        padding: 12px 20px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.3s;
    }

    .dropdown-content a:hover {
        background-color: rgba(255, 107, 156, 0.05);
        color: var(--pk);
        padding-left: 25px;
    }

    .dropdown-content hr {
        border: 0;
        border-top: 1px solid #f0f0f0;
        margin: 8px 0;
    }

    .user-info-box {
        padding: 10px 20px 15px;
        display: flex;
        flex-direction: column;
        border-bottom: 1px solid #f0f0f0;
        margin-bottom: 8px;
    }

    .user-name {
        font-weight: 800;
        color: #333;
        font-size: 1rem;
        font-family: 'Fredoka One', cursive;
        color: var(--pk);
    }

    .user-role {
        font-size: 0.75rem;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 2px;
    }

    .logout-link {
        color: #ff4757 !important;
    }

    .logout-link:hover {
        background-color: #fff1f2 !important;
    }

    .login-link {
        font-weight: 700 !important;
        color: var(--pk) !important;
    }

    @media (max-width: 900px) {
        .dropdown-content {
            position: fixed;
            right: 12px;
            top: 72px;
            max-width: calc(100vw - 24px);
        }
    }
</style>

<div class="menu-overlay" id="menuOverlay"></div>

<style>
    @if(isset($isUserPanel) && $isUserPanel)
    .mobile-menu.open {
        max-height: unset !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        height: calc(100vh - 68px) !important;
    }
    .mobile-menu ul {
        padding-bottom: 40px !important;
    }
    @endif
</style>

<div class="mobile-menu" id="mobileMenu" aria-hidden="true">
    <ul>
        @if(isset($isUserPanel) && $isUserPanel)
            <li class="mob-cat-title"
                style="font-family:'Fredoka One',cursive;font-size:.7rem;color:var(--pk);padding:10px 6%;letter-spacing:1px;text-transform:uppercase;">
                Main</li>
            <li><a href="{{ route('userdashboard') }}">Overview</a></li>
            <li><a href="{{ route('meal-plan') }}">Meal Plan</a></li>
            <li><a href="{{ route('supplement') }}">Supplement</a></li>
            <li><a href="{{ route('child-profile') }}">Child Profile</a></li>
            <li><a href="{{ route('growth-signal') }}">Growth Signals</a></li>
            <li><a href="{{ route('check-in') }}">Quarterly Check-in</a></li>
            <li><a href="{{ route('order') }}">Order</a></li>

            <li class="mob-cat-title"
                style="font-family:'Fredoka One',cursive;font-size:.7rem;color:var(--pk);padding:10px 6%;letter-spacing:1px;text-transform:uppercase;">
                Account</li>
            <li><a href="{{ route('personal-info') }}">Personal Info</a></li>
            <li><a href="#">Address</a></li>
            <li><a href="#">Wishlist</a></li>
            <li><a href="{{ route('change-password') }}">Change Password</a></li>
            <li><a href="{{ route('subscription') }}">Subscription</a></li>
            <li><a href="#">My Coupons</a></li>

            <li>
                <hr style="border:0; border-top:1px solid rgba(0,0,0,0.1); margin: 8px 6%;">
            </li>
            <li class="mob-cat-title"
                style="font-family:'Fredoka One',cursive;font-size:.7rem;color:var(--pk);padding:10px 6%;letter-spacing:1px;text-transform:uppercase;">
                Global Nav</li>
        @endif
        <li><a href="{{ route('home') }}"><span class="link-emoji"></span> Home</a></li>
        <li><a href="{{ route('about') }}"><span class="link-emoji"></span> About Us</a></li>
        <li><a href="{{ route('product') }}"><span class="link-emoji"></span> Products</a></li>
        <li><a href="{{ route('diet_chart') }}"><span class="link-emoji"></span> Personalized Diet Chart</a></li>
        <li><a href="{{ route('testimonials') }}"><span class="link-emoji"></span> Testimonials</a></li>
        <li><a href="{{ route('ingredients') }}"><span class="link-emoji"></span> Ingredients</a></li>
    </ul>
    <div class="mobile-cta-wrap">
        <a href="{{ route('contact') }}">Contact Us</a>
    </div>
</div>

<div id="cart-popup">
    <div class="popup-inner">
        <button type="button" class="close-cart" id="closeCart" aria-label="Close cart">&times;</button>
        <div class="cart-inner">
            <h4 class="title-text"> Cart Items <span id="cartPopupCount">0</span></h4>

            <div id="cartPopupItems"></div>

            <div class="text-box">
                <h5>Subtotal</h5>
                <span id="cartPopupSubtotal">Rs. 0</span>
            </div>

            <div class="btn-box">
                <a href="{{ route('cart.page') }}">View Cart</a>
                <a href="{{ route('checkout.index') }}">Checkout &rarr;</a>
            </div>
        </div>
    </div>
</div>
