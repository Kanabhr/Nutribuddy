@extends('layouts.admin')
@php
    $title = 'Categories';
    $subTitle = 'Ecommerce / Categories';
@endphp

@section('content')
    @include('admin.partials.messages')

    <div class="card border-0 radius-12 mb-24">
        <div class="card-header bg-base border-bottom py-16 px-24">
            <h5 class="card-title mb-0">Create Category</h5>
        </div>
        <div class="card-body p-24">
            <form method="POST" action="{{ route('admin.ecommerce.categories.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="f7:layers"></iconify-icon>
                        </span>
                        <input type="text" name="name" class="form-control" placeholder="Category Name" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Parent Category</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="lucide:folder-tree"></iconify-icon>
                        </span>
                        <select name="parent_id" class="form-select ps-40">
                            <option value="">None</option>
                            @foreach ($parentCategories as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check form-switch d-flex align-items-center gap-2 p-0 mb-8">
                        <input class="form-check-input m-0 float-none" type="checkbox" value="1" name="is_active" checked
                            id="create_is_active">
                        <label class="form-check-label m-0" for="create_is_active">Active</label>
                    </div>
                </div>


                <div class="col-12 mt-24">
                    <h6 class="mb-0">SEO Settings</h6>
                    <hr class="mt-8 mb-16">
                </div>

                <div class="col-12">
                    <label class="form-label">Meta Title (SEO)</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="lucide:type"></iconify-icon>
                        </span>
                        <input type="text" name="meta_title" class="form-control" placeholder="SEO Title">
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Meta Description (SEO)</label>
                    <textarea name="meta_description" class="form-control" rows="2"
                        placeholder="SEO Description"></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Meta Keywords (SEO)</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="lucide:key"></iconify-icon>
                        </span>
                        <input type="text" name="meta_keywords" class="form-control"
                            placeholder="keyword1, keyword2, keyword3">
                    </div>
                </div>
                <div class="col-12 mt-16">
                    <button type="submit" class="btn btn-primary-600">Create Category</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card basic-data-table border-0 radius-12 mb-24">
        <div class="card-header bg-base border-bottom py-16 px-24">
            <h5 class="card-title mb-0">Category List</h5>
        </div>
        <div class="card-body p-24">
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>

                            <th>Parent</th>
                            <th>Products</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td><span class="text-sm text-secondary-light">{{ $category->id }}</span></td>
                                <td><span class="text-md fw-semibold text-dark">{{ $category->name }}</span></td>

                                <td>
                                    @if($category->parent)
                                        <span class="badge bg-info-100 text-info-600 px-2">{{ $category->parent->name }}</span>
                                    @else
                                        <span class="text-secondary-light">Primary</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.ecommerce.products.index', ['category_id' => $category->id]) }}"
                                        class="badge bg-primary-100 text-primary-600 px-2 fw-bold">
                                        {{ $category->products()->count() }} Products
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @if($category->is_active)
                                            <span class="badge bg-success-100 text-success-600 w-fit">Active</span>
                                        @else
                                            <span class="badge bg-danger-100 text-danger-600 w-fit">Inactive</span>
                                        @endif

                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-success-600 radius-8 d-inline-flex align-items-center gap-1 edit-btn"
                                            data-bs-toggle="modal" data-bs-target="#editCategoryModal"
                                            data-id="{{ $category->id }}" data-name="{{ $category->name }}"
                                            data-slug="{{ $category->slug }}" data-parent_id="{{ $category->parent_id }}"
                                            data-description="{{ $category->description }}"
                                            data-meta_title="{{ $category->meta_title }}"
                                            data-meta_description="{{ $category->meta_description }}"
                                            data-meta_keywords="{{ $category->meta_keywords }}"
                                            data-sort_order="{{ $category->sort_order }}"
                                            data-is_active="{{ $category->is_active }}"
                                            data-action="{{ route('admin.ecommerce.categories.update', $category) }}">
                                            <iconify-icon icon="lucide:edit"></iconify-icon> Edit
                                        </button>
                                        <form method="POST"
                                            action="{{ route('admin.ecommerce.categories.destroy', $category) }}"
                                            class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="btn btn-sm btn-outline-danger-600 radius-8 d-inline-flex align-items-center gap-1"
                                                onclick="return confirm('Delete this category?')">
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

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editCategoryForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="f7:layers"></iconify-icon>
                                    </span>
                                    <input type="text" name="name" id="edit_name" class="form-control"
                                        placeholder="Category Name" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Parent Category</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="lucide:folder-tree"></iconify-icon>
                                    </span>
                                    <select name="parent_id" id="edit_parent_id" class="form-select ps-40">
                                        <option value="">None</option>
                                        @foreach ($parentCategories as $parent)
                                            <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3 d-flex align-items-end">
                                <input type="hidden" name="is_active" value="0">
                                <div class="form-check form-switch d-flex align-items-center gap-2 p-0 mb-8">
                                    <input class="form-check-input m-0 float-none" type="checkbox" value="1"
                                        name="is_active" id="edit_is_active">
                                    <label class="form-check-label m-0" for="edit_is_active">Active</label>
                                </div>
                            </div>


                            <div class="col-12 mt-24">
                                <h6 class="mb-0">SEO Settings</h6>
                                <hr class="mt-8 mb-16">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Meta Title (SEO)</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="lucide:type"></iconify-icon>
                                    </span>
                                    <input type="text" name="meta_title" id="edit_meta_title" class="form-control"
                                        placeholder="SEO Title">
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Meta Description (SEO)</label>
                                <textarea name="meta_description" id="edit_meta_description" class="form-control" rows="2"
                                    placeholder="SEO Description"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Meta Keywords (SEO)</label>
                                <div class="icon-field">
                                    <span class="icon">
                                        <iconify-icon icon="lucide:key"></iconify-icon>
                                    </span>
                                    <input type="text" name="meta_keywords" id="edit_meta_keywords" class="form-control"
                                        placeholder="keyword1, keyword2, keyword3">
                                </div>
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
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize DataTable
            if (document.getElementById('dataTable')) {
                new DataTable('#dataTable');
            }

            const editModal = document.getElementById('editCategoryModal');
            if (editModal) {
                editModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;

                    // Extract info from data-* attributes
                    const action = button.getAttribute('data-action');
                    const name = button.getAttribute('data-name');
                    const slug = button.getAttribute('data-slug');
                    const parentId = button.getAttribute('data-parent_id');
                    const sortOrder = button.getAttribute('data-sort_order');
                    const description = button.getAttribute('data-description');
                    const metaTitle = button.getAttribute('data-meta_title');
                    const metaDescription = button.getAttribute('data-meta_description');
                    const metaKeywords = button.getAttribute('data-meta_keywords');
                    const isActive = button.getAttribute('data-is_active');

                    // Update the modal's content.
                    const form = editModal.querySelector('#editCategoryForm');
                    form.setAttribute('action', action);

                    editModal.querySelector('#edit_name').value = name;

                    editModal.querySelector('#edit_parent_id').value = parentId || '';


                    editModal.querySelector('#edit_meta_title').value = metaTitle || '';
                    editModal.querySelector('#edit_meta_description').value = metaDescription || '';
                    editModal.querySelector('#edit_meta_keywords').value = metaKeywords || '';
                    editModal.querySelector('#edit_is_active').checked = isActive == '1';
                });
            }
        });
    </script>
@endsection