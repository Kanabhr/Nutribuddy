@extends('layouts.admin')
@php
    $title = 'Product Attributes';
    $subTitle = 'Ecommerce / Attributes';
@endphp

@section('content')
    @include('admin.partials.messages')

    <div class="card mb-24">
        <div class="card-header">
            <h5 class="card-title mb-0">Create Attribute</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.ecommerce.attributes.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Attribute Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Size, Color, Flavour" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Values</label>
                    <textarea name="values_text" class="form-control" rows="2" placeholder="Small, Medium, Large or Red, Blue, Green" required></textarea>
                    <small class="text-secondary-light">Separate values with commas or new lines.</small>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check form-switch mb-8">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary-600">Create Attribute</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card basic-data-table">
        <div class="card-header">
            <h5 class="card-title mb-0">Attribute List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable" data-page-length="10">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Values</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attributes as $attribute)
                            <tr>
                                <td class="fw-semibold">{{ $attribute->name }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach(($attribute->values ?? []) as $value)
                                            <span class="badge bg-primary-50 text-primary-600">{{ $value }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    @if($attribute->is_active)
                                        <span class="badge bg-success-100 text-success-600">Active</span>
                                    @else
                                        <span class="badge bg-danger-100 text-danger-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button"
                                        class="btn btn-sm btn-outline-success-600 edit-attribute-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editAttributeModal"
                                        data-action="{{ route('admin.ecommerce.attributes.update', $attribute) }}"
                                        data-name="{{ $attribute->name }}"
                                        data-values="{{ implode(', ', $attribute->values ?? []) }}"
                                        data-is_active="{{ $attribute->is_active ? 1 : 0 }}">
                                        Edit
                                    </button>
                                    <form method="POST" action="{{ route('admin.ecommerce.attributes.destroy', $attribute) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger-600" onclick="return confirm('Delete this attribute?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editAttributeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Attribute</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editAttributeForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Attribute Name</label>
                            <input type="text" name="name" id="edit_attribute_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Values</label>
                            <textarea name="values_text" id="edit_attribute_values" class="form-control" rows="3" required></textarea>
                        </div>
                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check form-switch mb-8">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_attribute_active">
                            <label class="form-check-label" for="edit_attribute_active">Active</label>
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
            if (document.getElementById('dataTable')) {
                new DataTable('#dataTable');
            }

            document.querySelectorAll('.edit-attribute-btn').forEach(button => {
                button.addEventListener('click', function () {
                    document.getElementById('editAttributeForm').setAttribute('action', this.dataset.action);
                    document.getElementById('edit_attribute_name').value = this.dataset.name;
                    document.getElementById('edit_attribute_values').value = this.dataset.values;
                    document.getElementById('edit_attribute_active').checked = this.dataset.is_active === '1';
                });
            });
        });
    </script>
@endsection
