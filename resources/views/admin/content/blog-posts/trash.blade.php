@extends('layouts.admin')
@php
    $title = 'Blog Trash';
    $subTitle = 'Ecommerce / Blog Posts / Trash';
@endphp

@section('content')
    @include('admin.partials.messages')

    <div class="card basic-data-table">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h5 class="card-title mb-0">Trash Blog Posts</h5>
            <a href="{{ route('admin.ecommerce.blog-posts.index') }}" class="btn btn-sm btn-outline-primary-600">
                <iconify-icon icon="lucide:arrow-left"></iconify-icon> Active Posts
                <span class="badge bg-primary-600 text-white ms-1">{{ $activeCount ?? 0 }}</span>
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.ecommerce.blog-posts.bulk-force-destroy') }}" id="bulkBlogDeleteForm" class="d-none">
                @csrf
                @method('DELETE')
            </form>
            <div class="d-flex justify-content-end mb-16">
                <button type="submit" form="bulkBlogDeleteForm" class="btn btn-sm btn-danger-600 d-inline-flex align-items-center gap-1" id="bulkBlogDeleteBtn" disabled
                    onclick="return confirm('Permanently delete selected blog posts? This cannot be undone.')">
                    <iconify-icon icon="mingcute:delete-2-line"></iconify-icon> Permanent Delete Selected
                </button>
            </div>
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable" data-page-length="10">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" class="form-check-input" id="selectAllPosts" aria-label="Select all blog posts">
                            </th>
                            <th>Post Title</th>
                            <th>Category</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Deleted At</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($posts as $post)
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
                                    <input type="checkbox" class="form-check-input post-check" name="post_ids[]" value="{{ $post->id }}" form="bulkBlogDeleteForm" aria-label="Select {{ $post->title }}">
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-md fw-bold text-dark">{{ $post->title }}</span>
                                        <small class="text-secondary-light fw-medium">Slug: {{ $post->slug }}</small>
                                    </div>
                                </td>
                                <td><span class="badge bg-info-100 text-info-600 px-2 fw-medium">{{ $post->category?->name ?? 'Uncategorized' }}</span></td>
                                <td><span class="text-sm text-secondary-light fw-medium">{{ $post->author?->name ?? 'Admin' }}</span></td>
                                <td>
                                    <span class="badge bg-{{ $statusClass }}-100 text-{{ $statusClass }}-600 px-2 fw-medium">
                                        {{ ucfirst($post->status) }}
                                    </span>
                                </td>
                                <td><span class="text-sm text-secondary-light fw-medium">{{ $post->deleted_at?->format('d M Y, h:i A') }}</span></td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <form method="POST" action="{{ route('admin.ecommerce.blog-posts.restore', $post->id) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-success-600 radius-8 d-inline-flex align-items-center gap-1">
                                                <iconify-icon icon="lucide:rotate-ccw"></iconify-icon> Restore
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.ecommerce.blog-posts.force-destroy', $post->id) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger-600 radius-8 d-inline-flex align-items-center gap-1"
                                                onclick="return confirm('Permanently delete this blog post? This cannot be undone.')">
                                                <iconify-icon icon="mingcute:delete-2-line"></iconify-icon> Permanent Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-secondary-light py-32">Trash is empty.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('dataTable')) {
            new DataTable('#dataTable');
        }

        const selectAll = document.getElementById('selectAllPosts');
        const bulkDeleteBtn = document.getElementById('bulkBlogDeleteBtn');

        function postChecks() {
            return Array.from(document.querySelectorAll('.post-check'));
        }

        function syncBulkState() {
            const checks = postChecks();
            const checkedCount = checks.filter((check) => check.checked).length;
            bulkDeleteBtn.disabled = checkedCount === 0;
            if (selectAll) {
                selectAll.checked = checks.length > 0 && checkedCount === checks.length;
                selectAll.indeterminate = checkedCount > 0 && checkedCount < checks.length;
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                postChecks().forEach((check) => {
                    check.checked = selectAll.checked;
                });
                syncBulkState();
            });
        }

        document.addEventListener('change', function(event) {
            if (event.target.classList.contains('post-check')) {
                syncBulkState();
            }
        });

        syncBulkState();
    });
</script>
