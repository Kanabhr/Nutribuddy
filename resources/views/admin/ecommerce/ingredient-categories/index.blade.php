@extends('layouts.admin')
@php
    $title = 'Ingredient Categories';
    $subTitle = 'Ecommerce / Ingredient Categories';
@endphp

@section('content')
    @include('admin.partials.messages')

    <div class="card border-0 radius-12 mb-24">
        <div class="card-header bg-base border-bottom py-16 px-24">
            <h5 class="card-title mb-0">Create Ingredient Category</h5>
        </div>
        <div class="card-body p-24">
            <form method="POST" action="{{ route('admin.ecommerce.ingredient-categories.store') }}" class="row g-3">
                @csrf
                <div class="col-md-8">
                    <label class="form-label">Name</label>
                    <div class="icon-field">
                        <span class="icon"><iconify-icon icon="f7:tag"></iconify-icon></span>
                        <input type="text" name="name" class="form-control" placeholder="Category Name" required>
                    </div>
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check form-switch d-flex align-items-center gap-2 p-0 mb-8">
                        <input class="form-check-input m-0 float-none" type="checkbox" value="1" name="is_active" id="createIsActive" checked>
                        <label class="form-check-label m-0" for="createIsActive">Active</label>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary-600">Create Category</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card basic-data-table border-0 radius-12 mb-24">
        <div class="card-header bg-base border-bottom py-16 px-24">
            <h5 class="card-title mb-0">Ingredient Category List</h5>
        </div>
        <div class="card-body p-24">
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Ingredients</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td>{{ $category->name }}</td>
                                <td><span class="badge bg-info-100 text-info-600">{{ $category->ingredients_count }}</span></td>
                                <td>
                                    @if($category->is_active)
                                        <span class="badge bg-success-100 text-success-600">Active</span>
                                    @else
                                        <span class="badge bg-danger-100 text-danger-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-success-600 edit-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editIngredientCategoryModal"
                                            data-name="{{ $category->name }}"
                                            data-sort_order="{{ $category->sort_order }}"
                                            data-is_active="{{ $category->is_active }}"
                                            data-action="{{ route('admin.ecommerce.ingredient-categories.update', $category) }}">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('admin.ecommerce.ingredient-categories.destroy', $category) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger-600" onclick="return confirm('Delete this ingredient category?')">Delete</button>
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

    <div class="modal fade" id="editIngredientCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Ingredient Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editIngredientCategoryForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check form-switch d-flex align-items-center gap-2 p-0 m-0">
                            <input class="form-check-input m-0 float-none" type="checkbox" value="1" name="is_active" id="edit_is_active">
                            <label class="form-check-label m-0" for="edit_is_active">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary-600">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('dataTable')) {
                new DataTable('#dataTable');
            }

            const editModal = document.getElementById('editIngredientCategoryModal');
            if (editModal) {
                editModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const form = editModal.querySelector('#editIngredientCategoryForm');
                    form.setAttribute('action', button.getAttribute('data-action'));

                    editModal.querySelector('#edit_name').value = button.getAttribute('data-name') || '';
                    editModal.querySelector('#edit_is_active').checked = button.getAttribute('data-is_active') === '1';
                });
            }
        });
    </script>
@endsection
