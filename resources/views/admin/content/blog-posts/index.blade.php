@extends('layouts.admin')
@php
    $title = 'Blog Posts';
    $subTitle = 'Ecommerce / Blog Posts';
@endphp

@section('content')
    <style>
        .ck-editor__editable_inline {
            min-height: 300px;
        }
    </style>
    @include('admin.partials.messages')

    <div class="card basic-data-table">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h5 class="card-title mb-0">Post List</h5>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.ecommerce.blog-posts.create') }}" class="btn btn-sm btn-primary-600 d-inline-flex align-items-center gap-1">
                    <iconify-icon icon="lucide:plus"></iconify-icon> Create Post
                </a>
                <a href="{{ route('admin.ecommerce.blog-posts.trash') }}" class="btn btn-sm btn-outline-danger-600 d-inline-flex align-items-center gap-1">
                    <iconify-icon icon="lucide:trash-2"></iconify-icon> Trash
                    @if(($trashCount ?? 0) > 0)
                        <span class="badge bg-danger-600 text-white ms-1">{{ $trashCount }}</span>
                    @endif
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>
                    <thead>
                        <tr>
                            <th>Post Title</th>
                            <th>Category</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($posts as $post)
                            @php
                                $statusClass = match(strtolower($post->status ?? '')) {
                                    'published' => 'success',
                                    'draft' => 'warning',
                                    'archived' => 'secondary',
                                    default => 'info'
                                };
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-md fw-bold text-dark">{{ $post->title }}</span>
                                        <small class="text-secondary-light fw-medium">Slug: {{ $post->slug }}</small>
                                    </div>
                                </td>
                                <td><span class="badge bg-info-100 text-info-600 px-2 fw-medium">{{ $post->category?->name ?? 'Uncategorized' }}</span></td>
                                <td><span class="text-sm text-secondary-light fw-medium">{{ $post->display_author }}</span></td>
                                <td>
                                    <span class="badge bg-{{ $statusClass }}-100 text-{{ $statusClass }}-600 px-2 fw-medium">
                                        {{ ucfirst($post->status) }}
                                    </span>
                                </td>
                                <td><span class="text-sm text-secondary-light fw-medium">{{ $post->published_at?->format('d M Y') ?? $post->created_at?->format('d M Y') ?? 'N/A' }}</span></td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <a href="{{ route('blog.show', $post->id) }}" target="_blank" class="btn btn-sm btn-outline-info-600 radius-8 d-inline-flex align-items-center gap-1" title="View on Storefront">
                                            <iconify-icon icon="lucide:external-link"></iconify-icon> View
                                        </a>
                                        <a href="{{ route('admin.ecommerce.blog-posts.edit', $post) }}" class="btn btn-sm btn-outline-success-600 radius-8 d-inline-flex align-items-center gap-1">
                                            <iconify-icon icon="lucide:edit"></iconify-icon> Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.ecommerce.blog-posts.destroy', $post) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger-600 radius-8 d-inline-flex align-items-center gap-1" onclick="return confirm('Move this post to trash?')">
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
            // Initialize DataTable
            if (document.getElementById('dataTable')) {
                new DataTable('#dataTable');
            }
        });
    </script>
@endsection
