<div class="navbar-header">
    <div class="row align-items-center justify-content-between flex-nowrap w-100 m-0">
        <div class="col-auto p-0">
            <div class="d-flex align-items-center gap-2 gap-sm-3 flex-nowrap">
                <button type="button" class="sidebar-toggle d-none d-xl-flex align-items-center justify-content-center" aria-label="Toggle Sidebar">
                    <i class="ri-menu-2-line icon text-2xl non-active"></i>
                    <i class="ri-arrow-right-line icon text-2xl active"></i>
                </button>
                <button type="button" class="sidebar-mobile-toggle d-flex d-xl-none align-items-center justify-content-center" aria-label="Toggle Navigation">
                    <i class="ri-menu-2-line icon text-2xl"></i>
                </button>
                <div class="nb-admin-title d-none d-md-block">
                    <span class="nb-admin-kicker">NutriBuddy Admin</span>
                </div>
            </div>
        </div>
        <div class="col-auto p-0">
            <div class="d-flex align-items-center gap-2 gap-sm-3 nb-admin-actions flex-nowrap">
                <div class="dropdown">
                    <button class="d-flex justify-content-center align-items-center rounded-circle nb-admin-notification" type="button" data-bs-toggle="dropdown" aria-label="Notifications">
                        <i class="ri-notification-3-line text-2xl"></i>
                        @if($unreadNotificationsCount > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 10px; padding: 4px 6px;">
                                {{ $unreadNotificationsCount }}
                            </span>
                        @endif
                    </button>
                    <div class="dropdown-menu to-top dropdown-menu-lg nb-admin-dropdown p-0 nb-notification-dropdown" style="width: min(320px, calc(100vw - 24px)); max-width: calc(100vw - 24px); overflow-x: hidden;">
                        <div class="p-16 border-bottom d-flex align-items-center justify-content-between">
                            <h6 class="mb-0">Notifications</h6>
                            @if($unreadNotificationsCount > 0)
                                <button type="button" id="navbar-mark-all-read" class="text-primary-600 text-sm fw-medium bg-transparent border-0" data-url="{{ route('admin.ecommerce.notifications.read-all') }}">Mark all as read</button>
                            @endif
                        </div>
                        <div class="notification-list" style="max-height: 350px; overflow-y: auto;">
                            @forelse($navbarNotifications as $notification)
                                @php
                                    $data = $notification->data;
                                    $isUnread = is_null($notification->read_at);
                                @endphp
                                <a href="{{ $data['action_url'] ?? 'javascript:void(0)' }}" class="dropdown-item p-16 border-bottom d-flex align-items-start gap-3 nb-notification-dropdown-item {{ $isUnread ? 'bg-primary-50' : '' }}">
                                    <div class="flex-shrink-0 w-40-px h-40-px radius-circle bg-primary-100 text-primary-600 d-flex align-items-center justify-content-center text-xl">
                                        <i class="ri-notification-3-line"></i>
                                    </div>
                                    <div class="flex-grow-1 nb-notification-dropdown-copy">
                                        <h6 class="text-sm mb-1 fw-bold {{ $isUnread ? 'text-primary-600' : 'text-secondary-light' }}">{{ $data['title'] ?? 'System Notification' }}</h6>
                                        <p class="text-xs mb-1 text-secondary-light">{{ Str::limit($data['message'] ?? '', 50) }}</p>
                                        <span class="text-xs text-secondary-light">{{ $notification->created_at->diffForHumans() }}</span>
                                    </div>
                                </a>
                            @empty
                                <div class="p-24 text-center">
                                    <p class="text-sm text-secondary-light mb-0">No new notifications</p>
                                </div>
                            @endforelse
                        </div>
                        <div class="p-12 text-center">
                            <a href="{{ route('admin.ecommerce.notifications.index') }}" class="text-primary-600 text-sm fw-semibold">View All Notifications</a>
                        </div>
                    </div>
                </div>
                <div class="dropdown">
                    <button class="d-flex justify-content-center align-items-center rounded-circle nb-admin-profile" type="button" data-bs-toggle="dropdown">
                        <img src="{{ asset('img/user.png') }}" alt="Admin" class="nb-admin-profile-img">
                    </button>
                    <div class="dropdown-menu to-top dropdown-menu-sm nb-admin-dropdown">
                        <div class="py-12 px-16 radius-8 bg-primary-50 mb-16 d-flex align-items-center justify-content-between gap-2">
                            <div>
                                <h6 class="text-lg text-primary-light fw-semibold mb-2">{{ auth()->user()->name ?? 'Guest Admin' }}</h6>
                                <span class="text-secondary-light fw-medium text-sm">{{ ucfirst(auth()->user()->role ?? 'Admin') }}</span>
                            </div>
                            <button type="button" class="hover-text-danger" data-bs-dismiss="dropdown" aria-label="Close">
                                <i class="ri-close-line icon text-xl"></i>
                            </button>
                        </div>
                        <ul class="to-top-list">
                            <li>
                                <a class="dropdown-item text-black px-0 py-8 hover-bg-transparent hover-text-danger d-flex align-items-center gap-3" href="javascript:void(0)" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="ri-logout-box-r-line icon text-xl"></i> Log Out
                                </a>
                                <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </li>
                        </ul>
                    </div>
                </div><!-- Profile dropdown end -->
            </div>
        </div>
    </div>
</div>

<style>
    .nb-notification-dropdown-item {
        min-width: 0;
        white-space: normal;
    }

    .nb-notification-dropdown-copy {
        min-width: 0;
    }

    .nb-notification-dropdown-copy h6,
    .nb-notification-dropdown-copy p {
        overflow-wrap: anywhere;
        word-break: break-word;
        white-space: normal;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const markAllBtn = document.getElementById('navbar-mark-all-read');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Keep dropdown open
                
                const url = this.getAttribute('data-url');
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI
                        document.querySelectorAll('.notification-list .dropdown-item').forEach(item => {
                            item.classList.remove('bg-primary-50');
                            const title = item.querySelector('h6');
                            if (title) {
                                title.classList.remove('text-primary-600');
                                title.classList.add('text-secondary-light');
                            }
                        });
                        const badge = document.querySelector('.nb-admin-notification .badge');
                        if (badge) badge.remove();
                        markAllBtn.remove();
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        }
    });
</script>
