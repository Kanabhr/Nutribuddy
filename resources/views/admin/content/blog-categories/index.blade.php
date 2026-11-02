@extends('layouts.admin')
@php
    $title = 'Blog Categories';
    $subTitle = 'Ecommerce / Blog Categories';
@endphp

@section('content')
    @include('admin.partials.messages')

    <div class="card mb-24">
        <div class="card-header">
            <h5 class="card-title mb-0">Create Blog Category</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.ecommerce.blog-categories.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="f7:tag"></iconify-icon>
                        </span>
                        <input type="text" name="name" class="form-control" placeholder="Category Name" required>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check form-switch d-flex align-items-center gap-2 mb-8">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" value="1" name="is_active" checked>
                        <label class="form-check-label mb-0">Active</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary-600">Create Category</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card basic-data-table">
        <div class="card-header">
            <h5 class="card-title mb-0">Category List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>
                    <thead>
                        <tr>
                            <th>Category Name</th>
                            <th>Post Count</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-md fw-bold text-dark">{{ $category->name }}</span>
                                    </div>
                                </td>

                                <td><span class="badge bg-info-100 text-info-600 px-2 fw-medium">{{ $category->posts_count }} Posts</span></td>
                                <td>
                                    @if($category->is_active)
                                        <span class="badge bg-success-100 text-success-600 px-2 fw-medium">Active</span>
                                    @else
                                        <span class="badge bg-danger-100 text-danger-600 px-2 fw-medium">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-success-600 radius-8 d-inline-flex align-items-center gap-1 edit-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editBlogCategoryModal"
                                            data-name="{{ $category->name }}"
                                            data-is_active="{{ $category->is_active }}"
                                            data-action="{{ route('admin.ecommerce.blog-categories.update', $category) }}">
                                            <iconify-icon icon="lucide:edit"></iconify-icon> Edit
                                        </button>
                                        <form method="POST" action="{{ route('admin.ecommerce.blog-categories.destroy', $category) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger-600 radius-8 d-inline-flex align-items-center gap-1" onclick="return confirm('Delete this category?')">
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

    <!-- Edit Blog Category Modal -->
    <div class="modal fade" id="editBlogCategoryModal" tabindex="-1" aria-labelledby="editBlogCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBlogCategoryModalLabel">Edit Blog Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editBlogCategoryForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <div class="icon-field">
                                <span class="icon">
                                    <iconify-icon icon="f7:tag"></iconify-icon>
                                </span>
                                <input type="text" name="name" id="edit_name" class="form-control" placeholder="Category Name" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch d-flex align-items-center gap-2">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" value="1" name="is_active" id="edit_is_active">
                                <label class="form-check-label mb-0" for="edit_is_active">Active</label>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary-600">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable
            if (document.getElementById('dataTable')) {
                new DataTable('#dataTable');
            }

            const editModal = document.getElementById('editBlogCategoryModal');
            if (editModal) {
                editModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    
                    const action = button.getAttribute('data-action');
                    const name = button.getAttribute('data-name');
                    const isActive = button.getAttribute('data-is_active');

                    const form = editModal.querySelector('#editBlogCategoryForm');
                    form.setAttribute('action', action);
                    
                    editModal.querySelector('#edit_name').value = name || '';
                    editModal.querySelector('#edit_is_active').checked = isActive == '1';
                });
            }
        });
    </script>
@endsection
