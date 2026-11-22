@extends('layouts.admin')
@php
    $title = 'Reviews & Ratings';
    $subTitle = 'Ecommerce / Reviews';
    $adminUser = auth('admin')->user();
    $canManage = $adminUser?->can('manage moderation') ?? false;
    $canViewCustomers = $adminUser?->canAny(['view customers', 'manage customers']) ?? false;
@endphp

@section('content')
    @include('admin.partials.messages')

    @if ($errors->has('admin_reply'))
        <div class="alert alert-danger">{{ $errors->first('admin_reply') }}</div>
    @endif

    <div class="card basic-data-table">
        <div class="card-header">
            <h5 class="card-title mb-0">Product Reviews Management</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Customer</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Status/Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reviews as $review)
                            @php $stats = $customerStats[$review->user_id] ?? null; @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @php
                                            $primaryImage = $review->product->images->where('is_primary', true)->first() ?? $review->product->images->first();
                                        @endphp
                                        <img src="{{ $primaryImage ? asset('storage/' . $primaryImage->image_path) : asset('assets/images/logo-icon.png') }}" 
                                            alt="" class="w-40-px h-40-px radius-8 border flex-shrink-0 object-fit-cover">
                                        <div class="flex-grow-1">
                                            <h6 class="text-md mb-0 fw-semibold text-dark">{{ $review->product->name }}</h6>
                                            <span class="text-xs text-secondary-light">SKU: {{ $review->product->sku }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <button type="button"
                                            class="btn btn-link p-0 text-start text-md fw-medium text-primary-600 text-decoration-none"
                                            data-bs-toggle="modal" data-bs-target="#customerModal"
                                            data-name="{{ $review->user->name }}"
                                            data-email="{{ $review->user->email }}"
                                            data-phone="{{ $review->user->phone }}"
                                            data-orders="{{ (int) ($stats->orders_count ?? 0) }}"
                                            data-spent="{{ number_format((float) ($stats->total_spent ?? 0), 2) }}"
                                            data-coins="{{ (int) $review->user->coins_balance }}"
                                            data-profile="{{ $canViewCustomers ? route('admin.ecommerce.customers.show', $review->user_id) : '' }}">
                                            {{ $review->user->name }}
                                        </button>
                                        <span class="text-xs text-secondary-light">{{ $review->user->email }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1 text-warning-main">
                                        @for ($i = 1; $i <= 5; $i++)
                                            @if ($i <= $review->rating)
                                                <iconify-icon icon="solar:star-bold"></iconify-icon>
                                            @else
                                                <iconify-icon icon="solar:star-outline" class="text-secondary-light"></iconify-icon>
                                            @endif
                                        @endfor
                                        <span class="text-xs fw-bold text-dark ms-1">({{ $review->rating }})</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="max-w-200-px text-truncate text-sm text-secondary-light" title="{{ $review->comment }}">
                                        {{ $review->comment ?? 'No comment' }}
                                    </div>
                                    @php
                                        $mediaList = [];
                                        if (!empty($review->images)) {
                                            $mediaList = $review->images;
                                        } elseif ($review->image_path) {
                                            $mediaList = [$review->image_path];
                                        }
                                    @endphp
                                    @if(count($mediaList) > 0)
                                        <div class="d-flex gap-2 mt-2 flex-wrap">
                                            @foreach($mediaList as $img)
                                                <a href="{{ asset('storage/' . $img) }}" target="_blank">
                                                    <img src="{{ asset('storage/' . $img) }}" class="w-32-px h-32-px radius-4 border object-fit-cover" title="Click to view">
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($review->video_path)
                                        <div class="mt-2">
                                            <a href="{{ asset('storage/' . $review->video_path) }}" target="_blank" class="badge bg-primary-100 text-primary-600 text-xs text-decoration-none">
                                                <iconify-icon icon="solar:play-circle-bold" class="me-1"></iconify-icon> Video
                                            </a>
                                        </div>
                                    @endif
                                    @if($review->admin_reply)
                                        <div class="mt-2 p-2 radius-8 border-start border-3 border-primary-600 bg-neutral-100">
                                            <span class="text-xs fw-semibold text-primary-600 d-block">
                                                Brand reply{{ $review->admin_replied_at ? ' · ' . $review->admin_replied_at->format('d M Y') : '' }}
                                            </span>
                                            <span class="text-sm text-secondary-light">{{ $review->admin_reply }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="form-check form-switch py-0 mb-0">
                                                <input class="form-check-input review-toggle" type="checkbox"
                                                    {{ $review->is_active ? 'checked' : '' }}
                                                    data-id="{{ $review->id }}"
                                                    data-url="{{ route('admin.ecommerce.reviews.update', $review) }}"
                                                    data-token="{{ csrf_token() }}">
                                            </div>
                                            <span class="badge review-badge-{{ $review->id }} {{ $review->is_active ? 'bg-success-100 text-success-600' : 'bg-danger-100 text-danger-600' }} px-2">
                                                {{ $review->is_active ? 'Published' : 'Hidden' }}
                                            </span>
                                        </div>
                                        <small class="text-xs text-secondary-light">{{ optional($review->created_at)->format('d M Y') ?? 'N/A' }}</small>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        @if ($canManage)
                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary-600 radius-8 d-inline-flex align-items-center gap-1"
                                                data-bs-toggle="modal" data-bs-target="#replyModal"
                                                data-action="{{ route('admin.ecommerce.reviews.reply', $review) }}"
                                                data-customer="{{ $review->user->name }}"
                                                data-comment="{{ $review->comment }}"
                                                data-reply="{{ $review->admin_reply }}">
                                                <iconify-icon icon="solar:chat-round-dots-linear"></iconify-icon>
                                                {{ $review->admin_reply ? 'Edit reply' : 'Reply' }}
                                            </button>
                                        @endif
                                        <form method="POST" action="{{ route('admin.ecommerce.reviews.destroy', $review) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger-600 radius-8 d-inline-flex align-items-center gap-1" onclick="return confirm('Delete this review permanently?')">
                                                <iconify-icon icon="mingcute:delete-2-line"></iconify-icon> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Reply modal --}}
    <div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title" id="replyModalLabel">Reply to <span data-fill="customer"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-sm text-secondary-light mb-3" data-fill="comment"></p>
                    <label for="admin_reply" class="form-label fw-semibold text-sm">Your reply</label>
                    <textarea id="admin_reply" name="admin_reply" rows="4" maxlength="2000" class="form-control"></textarea>
                    <small class="text-xs text-secondary-light">Leave empty and save to remove an existing reply.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary radius-8" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary-600 radius-8">Save reply</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Customer details modal --}}
    <div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="customerModalLabel" data-fill="name"></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0 text-sm">
                        <dt class="col-5 text-secondary-light">Email</dt><dd class="col-7 text-break" data-fill="email"></dd>
                        <dt class="col-5 text-secondary-light">Phone</dt><dd class="col-7" data-fill="phone"></dd>
                        <dt class="col-5 text-secondary-light">Orders</dt><dd class="col-7" data-fill="orders"></dd>
                        <dt class="col-5 text-secondary-light">Total spent</dt><dd class="col-7" data-fill="spent"></dd>
                        <dt class="col-5 text-secondary-light">NB Coins</dt><dd class="col-7" data-fill="coins"></dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-sm btn-outline-primary-600 radius-8 d-none" data-fill="profile">View full profile</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('dataTable')) {
                new DataTable('#dataTable');
            }

            document.querySelectorAll('.review-toggle').forEach(function(toggle) {
                toggle.addEventListener('change', function() {
                    const id = this.dataset.id;
                    const url = this.dataset.url;
                    const token = this.dataset.token;
                    const isActive = this.checked ? 1 : 0;
                    const badge = document.querySelector('.review-badge-' + id);

                    fetch(url, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ is_active: isActive }),
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            if (data.is_active) {
                                badge.className = 'badge review-badge-' + id + ' bg-success-100 text-success-600 px-2';
                                badge.textContent = 'Published';
                            } else {
                                badge.className = 'badge review-badge-' + id + ' bg-danger-100 text-danger-600 px-2';
                                badge.textContent = 'Hidden';
                            }
                        }
                    })
                    .catch(() => {
                        // Revert toggle on error
                        toggle.checked = !toggle.checked;
                        alert('Failed to update review status. Please try again.');
                    });
                });
            });

            // Reply modal: fill from the clicked button (textContent only, no HTML injection).
            const replyModal = document.getElementById('replyModal');
            if (replyModal) {
                replyModal.addEventListener('show.bs.modal', function (event) {
                    const btn = event.relatedTarget;
                    if (!btn) return;
                    replyModal.querySelector('form').action = btn.dataset.action;
                    replyModal.querySelector('[data-fill="customer"]').textContent = btn.dataset.customer || '';
                    replyModal.querySelector('[data-fill="comment"]').textContent = btn.dataset.comment || 'No comment';
                    replyModal.querySelector('textarea').value = btn.dataset.reply || '';
                });
            }

            // Customer popup.
            const customerModal = document.getElementById('customerModal');
            if (customerModal) {
                customerModal.addEventListener('show.bs.modal', function (event) {
                    const btn = event.relatedTarget;
                    if (!btn) return;
                    const d = btn.dataset;
                    const set = (key, value) => {
                        customerModal.querySelector('[data-fill="' + key + '"]').textContent = value || '—';
                    };
                    set('name', d.name);
                    set('email', d.email);
                    set('phone', d.phone);
                    set('orders', d.orders);
                    set('spent', d.spent ? '₹' + d.spent : '');
                    set('coins', d.coins);
                    const profile = customerModal.querySelector('a[data-fill="profile"]');
                    if (d.profile) {
                        profile.href = d.profile;
                        profile.classList.remove('d-none');
                    } else {
                        profile.classList.add('d-none');
                    }
                });
            }
        });
    </script>
@endsection