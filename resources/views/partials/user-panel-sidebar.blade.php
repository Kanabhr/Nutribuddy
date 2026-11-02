@php
    $route = Route::currentRouteName();
    $sidebarUser = Auth::user();
    $sidebarAvatar = $sidebarUser->avatar
        ? asset('storage/'.$sidebarUser->avatar)
        : ($sidebarUser->google_avatar ?: null);
@endphp

<div class="profile-block">
    <div class="avatar" style="{{ $sidebarAvatar ? 'padding:0;overflow:hidden;' : '' }}">
        @if($sidebarAvatar)
            <img src="{{ $sidebarAvatar }}" alt="{{ $sidebarUser->name }}"
                 style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">
        @else
            {{ substr($sidebarUser->name, 0, 1) }}
        @endif
        <div class="online-dot"></div>
    </div>
    <div class="profile-name">{{ $sidebarUser->name }}</div>
    <div class="profile-email">{{ $sidebarUser->email }}</div>
</div>
<div class="sidebar-nav-scroll" style="display:flex;flex:1;flex-direction:column;overflow-y:auto;">
    @php
        $activeDietGroup = request()->routeIs('meal-plan', 'nutrition.index', 'supplement', 'child-profile', 'growth-signal', 'check-in');
        $activeAssessmentGroup = request()->routeIs('user.assessment.*');
        $activeOrdersGroup = request()->routeIs('userdashboard', 'wallet', 'order', 'user.orders.returns.*', 'user.invoices.*', 'user.reviews.*', 'user.support-tickets');
    @endphp

    <div class="nav-section nav-group">
        <button type="button" class="nav-group-toggle {{ $activeOrdersGroup ? 'is-active' : '' }}" aria-expanded="true" aria-controls="sidebarOrdersLinks" data-sidebar-group-toggle>
            <span class="nav-label" style="padding:0!important;margin:0!important;">Orders &amp; Products</span>
            <svg class="nav-group-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="nav-group-links" id="sidebarOrdersLinks">
            <a href="{{ route('userdashboard') }}" class="nav-item {{ request()->routeIs('userdashboard') ? 'active' : '' }}" onclick="setActive(this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>
                Overview
            </a>
            <a href="{{ route('wallet') }}" class="nav-item {{ request()->routeIs('wallet') ? 'active' : '' }}" onclick="setActive(this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="8"/><path d="M12 8v8M8 12h8"/></svg>
                NB Coins Wallet
                <span class="nbadge" style="background: var(--or); margin-left: auto;">{{ Auth::user()->coins_balance }}</span>
            </a>
            <a href="{{ route('order') }}" class="nav-item {{ request()->routeIs('order') ? 'active' : '' }}" onclick="setActive(this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Order
                @if(Auth::user()->orders()->count() > 0)<span class="nbadge">{{ Auth::user()->orders()->count() }}</span>@endif
            </a>
            <a href="{{ route('user.orders.returns.index') }}" class="nav-item {{ request()->routeIs('user.orders.returns.*') ? 'active' : '' }}" onclick="setActive(this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M11 15h2a2 2 0 1 0 0-4h-2a2 2 0 1 1 0-4h2"/><path d="M12 17v2M12 5v2"/><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Returns
            </a>
            <a href="{{ route('user.invoices.index') }}" class="nav-item {{ request()->routeIs('user.invoices.*') ? 'active' : '' }}" onclick="setActive(this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Invoices
            </a>
            <a href="{{ route('user.reviews.index') }}" class="nav-item {{ request()->routeIs('user.reviews.index') ? 'active' : '' }}" onclick="setActive(this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                My Reviews
            </a>
            <a href="{{ route('user.support-tickets') }}" class="nav-item {{ request()->routeIs('user.support-tickets') ? 'active' : '' }}" onclick="setActive(this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                Support Tickets
            </a>
        </div>
    </div>

    <div class="nav-section nav-group">
        <button type="button" class="nav-group-toggle {{ $activeDietGroup ? 'is-active' : '' }}" aria-expanded="{{ $activeDietGroup ? 'true' : 'false' }}" aria-controls="sidebarDietLinks" data-sidebar-group-toggle>
            <span class="nav-label" style="padding:0!important;margin:0!important;">Diet Chart</span>
            <svg class="nav-group-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="nav-group-links" id="sidebarDietLinks" @if(!$activeDietGroup) hidden @endif>
          <a href="{{ route('meal-plan') }}" class="nav-item {{ request()->routeIs('meal-plan') ? 'active' : '' }}" onclick="setActive(this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
           Meal Plan
        </a>

          <a href="{{ route('nutrition.index') }}" class="nav-item {{ request()->routeIs('nutrition.index') ? 'active' : '' }}" onclick="setActive(this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
            </svg>
           Nutrition
        </a>

          <a href="{{ route('supplement') }}" class="nav-item {{ request()->routeIs('supplement') ? 'active' : '' }}" onclick="setActive(this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
         Supplement
        </a>
          <a href="{{ route('child-profile') }}" class="nav-item {{ request()->routeIs('child-profile') ? 'active' : '' }}" onclick="setActive(this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
         Child Profile
        </a>
        <a href="{{ route('growth-signal') }}" class="nav-item {{ request()->routeIs('growth-signal') ? 'active' : '' }}" onclick="setActive(this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>
                <polyline points="16 7 22 7 22 13"/>
            </svg>
            Growth Signals
        </a>
        <a href="{{ route('check-in') }}" class="nav-item {{ request()->routeIs('check-in') ? 'active' : '' }}" onclick="setActive(this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <polyline points="9 11 11 13 15 9"/>
            </svg>
            Quarterly Check-in
        </a>

    </div>

    <div class="nav-section nav-group">
        <button type="button" class="nav-group-toggle {{ $activeAssessmentGroup ? 'is-active' : '' }}" aria-expanded="{{ $activeAssessmentGroup ? 'true' : 'false' }}" aria-controls="sidebarAssessmentLinks" data-sidebar-group-toggle>
            <span class="nav-label" style="padding:0!important;margin:0!important;">Health Assessment</span>
            <svg class="nav-group-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="nav-group-links" id="sidebarAssessmentLinks" @if(!$activeAssessmentGroup) hidden @endif>
        <a href="{{ route('user.assessment.index') }}" class="nav-item {{ request()->routeIs('user.assessment.index') ? 'active' : '' }}" onclick="setActive(this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M9 11l3 3L22 4"/>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
            </svg>
            Start Assessment
        </a>
        <a href="{{ route('user.assessment.history') }}" class="nav-item {{ request()->routeIs('user.assessment.history') ? 'active' : '' }}" onclick="setActive(this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
            Assessment History
            @php $attemptCount = auth()->user()->assessmentAttempts()->count(); @endphp
            @if($attemptCount > 0)
                <span class="nbadge" style="background:var(--pu);margin-left:auto;">{{ $attemptCount }}</span>
            @endif
        </a>
        @php $latestAttempt = auth()->user()->assessmentAttempts()->latest()->first(); @endphp
        @if($latestAttempt)
        <a href="{{ route('user.assessment.result', $latestAttempt) }}" class="nav-item {{ request()->routeIs('user.assessment.result') ? 'active' : '' }}" onclick="setActive(this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
            Latest Result
            <span class="nbadge" style="background:var(--mn);margin-left:auto;">{{ number_format($latestAttempt->percentage, 0) }}%</span>
        </a>
        @endif

        </div>
    </div>

    <div class="nav-section">
        <span class="nav-label">Account</span>
        <a href="{{ route('personal-info') }}" class="nav-item {{ request()->routeIs('personal-info') ? 'active' : '' }}" onclick="setActive(this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
            Personal Info
        </a>
        <a href="#" class="nav-item" onclick="setActive(this)" style="display:none;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                <circle cx="12" cy="10" r="3" />
            </svg>
            Address
        </a>
        <a href="#" class="nav-item" onclick="setActive(this)" style="display:none;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
            Wishlist
        </a>
        <a href="{{ route('change-password') }}" class="nav-item {{ request()->routeIs('change-password') ? 'active' : '' }}" onclick="setActive(this)" style="display:none;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
            </svg>
            Change Password
        </a>
        <a href="{{ route('subscription') }}" class="nav-item {{ request()->routeIs('subscription') ? 'active' : '' }}" onclick="setActive(this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
            </svg>
            Subscription
        </a>
        <a href="{{ route('my-coupons') }}" class="nav-item {{ request()->routeIs('my-coupons') ? 'active' : '' }}" onclick="setActive(this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
                <line x1="1" y1="10" x2="23" y2="10" />
            </svg>
            My Coupons
            @php
                $usedCouponCount = \App\Models\CouponUsage::where('user_id', auth()->id())->count();
            @endphp
            @if($usedCouponCount > 0)
                <span class="nbadge">{{ $usedCouponCount }}</span>
            @endif
        </a>
    </div>
</div>
<div class="sidebar-footer">
    <form action="{{ route('frontend.logout') }}" method="POST" id="sidebarLogoutForm">
        @csrf
        <button type="submit" class="logout-btn">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                <polyline points="16 17 21 12 16 7" />
                <line x1="21" y1="12" x2="9" y2="12" />
            </svg>
            Logout
        </button>
    </form>
</div>
