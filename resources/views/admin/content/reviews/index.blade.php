@extends('layouts.admin')
@php
    $title = 'Reviews & Ratings';
    $subTitle = 'Ecommerce / Reviews';
@endphp

@section('content')
    @include('admin.partials.messages')

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
                                        <h6 class="text-md mb-0 fw-medium text-dark">{{ $review->user->name }}</h6>
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
        });
    </script>
@endsection
