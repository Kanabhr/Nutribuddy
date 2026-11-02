@php
    $sideSectionLogo = \App\Models\Setting::get('side_section_logo');
    $sideSectionLogoExists = $sideSectionLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($sideSectionLogo);
    $sideSectionLogoUrl = $sideSectionLogoExists
        ? asset('storage/' . $sideSectionLogo) . '?v=' . \Illuminate\Support\Facades\Storage::disk('public')->lastModified($sideSectionLogo)
        : asset('img/logo.png');
@endphp

<aside class="sidebar">
    <button type="button" class="sidebar-close-btn" aria-label="Close Sidebar">
        <i class="ri-close-line text-xl"></i>
    </button>
    <div>
        <a href="{{ route('admin.index') }}" class="sidebar-logo">
            <img src="{{ $sideSectionLogoUrl }}" alt="site logo" class="light-logo">
            <img src="{{ $sideSectionLogoUrl }}" alt="site logo" class="dark-logo">
            <img src="{{ asset('assets/images/logo-icon.png') }}" alt="site logo" class="logo-icon">
            <span class="nb-sidebar-brand-text">
                <strong>NutriBuddy</strong>
                <small>Admin Panel</small>
            </span>
        </a>
    </div>
    <div class="sidebar-menu-area">
        <ul class="sidebar-menu" id="sidebar-menu">

            @canany(['view dashboard', 'manage dashboard'])
            <li>
                <a href="{{ route('admin.index') }}">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="menu-icon"></iconify-icon>
                    <span>Dashboard</span>
                </a>
            </li>
            @endcan

            @canany(['view analytics', 'manage analytics'])
            <li>
                <a href="{{ route('admin.analytics') }}">
                    <iconify-icon icon="solar:chart-2-outline" class="menu-icon"></iconify-icon>
                    <span>Analytics</span>
                </a>
            </li>
            @endcan

            @canany(['view diet chart', 'manage diet chart'])
            <li class="dropdown">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:health-outline" class="menu-icon"></iconify-icon>
                    <span>Diet Chart</span>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="{{ route('admin.ecommerce.diet-plans.index') }}"><i
                                class="ri-circle-fill circle-icon text-pink w-auto"></i> Diet Plans</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.nutrition.questions.index') }}"><i
                                class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Questions</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.nutrition.diet-subscriptions.index') }}"><i
                                class="ri-circle-fill circle-icon text-success-main w-auto"></i> Diet Subscriptions</a>
                    </li>
                    @can('manage diet chart')
                    <li>
                        <a href="{{ route('admin.nutrition.leads.index') }}"><i
                                class="ri-circle-fill circle-icon text-danger-main w-auto"></i> Nutrition Leads</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.nutrition.lead-queue.index') }}"><i
                                class="ri-circle-fill circle-icon text-info-main w-auto"></i> Diet Chart Generation</a>
                    </li>
                    @endcan
                    <li>
                        <a href="{{ route('admin.nutrition.foods.index') }}"><i
                                class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Master Food Data</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.nutrition.diet-rules.index') }}"><i
                                class="ri-circle-fill circle-icon text-lilac-600 w-auto"></i> Diet Generation Rules</a>
                    </li>
                </ul>
            </li>
            @endcan

            @canany(['view products', 'manage products'])
            <li class="dropdown">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:box-outline" class="menu-icon"></iconify-icon>
                    <span>Products Management</span>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="{{ route('admin.ecommerce.categories.index') }}"><i
                                class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Categories</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ecommerce.products.index') }}"><i
                                class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Products</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ecommerce.products.trash') }}"><i
                                class="ri-circle-fill circle-icon text-danger-main w-auto"></i> Product Trash</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ecommerce.attributes.index') }}"><i
                                class="ri-circle-fill circle-icon text-info-main w-auto"></i> Attributes</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ecommerce.ingredient-categories.index') }}"><i class="ri-circle-fill circle-icon text-success-main w-auto"></i> Ingredient Categories</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ecommerce.ingredients.index') }}"><i class="ri-circle-fill circle-icon text-danger-main w-auto"></i> Ingredients</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ecommerce.tax-rates.index') }}"><i
                                class="ri-circle-fill circle-icon text-info-main w-auto"></i> Tax Rates</a>
                    </li>
                </ul>
            </li>
            @endcan

            @canany(['view orders', 'manage orders'])
            <li class="dropdown">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:cart-large-minimalistic-outline" class="menu-icon"></iconify-icon>
                    <span>Orders & Sales</span>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="{{ route('admin.ecommerce.orders.index') }}"><i
                                class="ri-circle-fill circle-icon text-success-main w-auto"></i> Orders</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ecommerce.invoices.index') }}"><i
                                class="ri-circle-fill circle-icon text-info-main w-auto"></i> Invoices</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ecommerce.order-returns.index') }}"><i
                                class="ri-circle-fill circle-icon text-danger-main w-auto"></i> Order Returns</a>
                    </li>
                </ul>
            </li>
            @endcan

            @canany(['view customers', 'manage customers'])
            <li class="dropdown">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:users-group-two-rounded-outline" class="menu-icon"></iconify-icon>
                    <span>Customer Relations</span>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="{{ route('admin.ecommerce.customers.index') }}"><i
                                class="ri-circle-fill circle-icon text-info-600 w-auto"></i> Customers</a>
                    </li>
                </ul>
            </li>
            @endcan

            @canany(['view promotions', 'manage promotions'])
            <li class="dropdown">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:tag-outline" class="menu-icon"></iconify-icon>
                    <span>Promotions</span>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="{{ route('admin.ecommerce.coupons.index') }}"><i
                                class="ri-circle-fill circle-icon text-danger-main w-auto"></i> Coupons</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ecommerce.newsletter.index') }}"><i
                                class="ri-circle-fill circle-icon text-cyan w-auto"></i> Newsletter</a>
                    </li>
                </ul>
            </li>
            @endcan

            @canany(['view loyalty', 'manage loyalty'])
            <li class="dropdown">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:medal-ribbon-star-outline" class="menu-icon"></iconify-icon>
                    <span>Loyalty & Coins</span>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="{{ route('admin.ecommerce.loyalty.settings') }}"><i
                                class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Coin Settings</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ecommerce.loyalty.transactions') }}"><i
                                class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Transactions</a>
                    </li>
                </ul>
            </li>
            @endcan

            @canany(['view moderation', 'manage moderation'])
            <li class="dropdown">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:star-outline" class="menu-icon"></iconify-icon>
                    <span>Moderation</span>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="{{ route('admin.ecommerce.reviews.index') }}"><i
                                class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Reviews</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ecommerce.video-reviews.index') }}"><i
                                class="ri-circle-fill circle-icon text-danger-main w-auto"></i> Video Reviews</a>
                    </li>
                </ul>
            </li>
            @endcan

            @canany(['view administration', 'manage administration'])
            <li class="dropdown">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:tuning-square-2-outline" class="menu-icon"></iconify-icon>
                    <span>Administration</span>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="{{ route('admin.ecommerce.notifications.index') }}"><i
                                class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Notifications</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ecommerce.support-tickets.index') }}"><i
                                class="ri-circle-fill circle-icon text-pink w-auto"></i> Support Tickets</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ecommerce.contact-leads.index') }}"><i
                                class="ri-circle-fill circle-icon text-orange w-auto"></i> Contact Leads</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ecommerce.side-section.index') }}"><i
                                class="ri-circle-fill circle-icon text-info-600 w-auto"></i> Header & Footer</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ecommerce.settings.general') }}"><i
                                class="ri-circle-fill circle-icon text-success w-auto"></i> SEO Settings</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ecommerce.settings.payment-gateways') }}"><i
                                class="ri-circle-fill circle-icon text-warning w-auto"></i> Payment Gateways</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ecommerce.settings.auth') }}"><i
                                class="ri-circle-fill circle-icon text-purple w-auto"></i> Auth Settings</a>
                    </li>
                </ul>
            </li>
            @endcan

            @canany(['view content', 'manage content'])
            <li class="dropdown">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:document-text-outline" class="menu-icon"></iconify-icon>
                    <span>Content Management</span>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="{{ route('admin.ecommerce.blog-categories.index') }}"><i
                                class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Blog Categories</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ecommerce.blog-posts.create') }}"><i
                                class="ri-circle-fill circle-icon text-info-main w-auto"></i> Create Blog Post</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ecommerce.blog-posts.index') }}"><i
                                class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Blog Posts</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.ecommerce.blog-posts.trash') }}"><i
                                class="ri-circle-fill circle-icon text-danger-main w-auto"></i> Blog Trash</a>
                    </li>
                </ul>
            </li>
            @endcan
            @canany(['view assessment', 'manage assessment'])
            <li class="dropdown">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:clipboard-list-outline" class="menu-icon"></iconify-icon>
                    <span>Assessment</span>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="{{ route('admin.assessment.questions.index') }}"><i
                                class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Questions</a>
                    </li>
                </ul>
            </li>
            @endcan

            @canany(['view users', 'manage users', 'view roles', 'manage roles'])
            <li>
                <a href="{{ route('admin.access-control.index') }}">
                    <iconify-icon icon="solar:shield-user-outline" class="menu-icon"></iconify-icon>
                    <span>Access Control</span>
                </a>
            </li>
            @endcan

            <li>
                <a href="javascript:void(0)"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <iconify-icon icon="lucide:power" class="menu-icon"></iconify-icon>
                    <span>Log Out</span>
                </a>
            </li>
        </ul>
    </div>
</aside>
