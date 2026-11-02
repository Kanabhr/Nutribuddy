@extends('layouts.admin')
@php
    $title = 'Ingredients';
    $subTitle = 'Ecommerce / Ingredients';
@endphp

@section('content')
    @include('admin.partials.messages')

    <div class="card basic-data-table border-0 radius-12 mb-24">
        <div class="card-header bg-base border-bottom d-flex align-items-center justify-content-between py-16 px-24">
            <h5 class="card-title mb-0">Ingredient List</h5>
            <a href="{{ route('admin.ecommerce.ingredients.create') }}" class="btn btn-sm btn-primary-600">Add Ingredient</a>
        </div>
        <div class="card-body p-24">
            <div class="table-responsive">
                <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>
                    <thead>
                        <tr>
                            <th>Icon</th>
                            <th>Main Heading</th>
                            <th>Short Heading</th>
                            <th>Category</th>
                            <th>Benefits</th>
                            <th>Featured</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ingredients as $ingredient)
                            <tr>
                                <td>
                                    <img src="{{ $ingredient->icon_path ? asset('storage/' . $ingredient->icon_path) : asset('assets/images/logo-icon.png') }}" alt="icon" class="w-48-px h-48-px radius-8 border object-fit-cover" onerror="this.onerror=null;this.src='{{ asset('assets/images/logo-icon.png') }}';">
                                </td>
                                <td class="fw-semibold">{{ $ingredient->main_heading }}</td>
                                <td>{{ $ingredient->short_heading }}</td>
                                <td>{{ $ingredient->category?->name ?? 'Uncategorized' }}</td>
                                <td><span class="badge bg-info-100 text-info-600">{{ $ingredient->benefits->count() }}</span></td>
                                <td>
                                    @if($ingredient->is_featured)
                                        <span class="badge bg-warning-100 text-warning-600">⭐ Homepage</span>
                                    @else
                                        <span class="badge bg-neutral-100 text-neutral-600">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($ingredient->is_active)
                                        <span class="badge bg-success-100 text-success-600">Active</span>
                                    @else
                                        <span class="badge bg-danger-100 text-danger-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('admin.ecommerce.ingredients.show', $ingredient) }}" class="btn btn-sm btn-outline-info-600">Show</a>
                                        <a href="{{ route('admin.ecommerce.ingredients.edit', $ingredient) }}" class="btn btn-sm btn-outline-success-600">Edit</a>
                                        <form method="POST" action="{{ route('admin.ecommerce.ingredients.destroy', $ingredient) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger-600" onclick="return confirm('Delete this ingredient?')">Delete</button>
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
        });
    </script>
@endsection
