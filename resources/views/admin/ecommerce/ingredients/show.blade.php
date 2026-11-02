@extends('layouts.admin')
@php
    $title = 'Ingredient Details';
    $subTitle = 'Ecommerce / Ingredients / Show';
@endphp

@section('content')
    <div class="card border-0 radius-12 mb-4">
        <div class="card-header bg-base border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2 py-16 px-24">
            <div>
                <h5 class="card-title mb-2">Ingredient Details</h5>
                <p class="text-secondary-light mb-0 text-sm">Complete overview of selected ingredient record</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.ecommerce.ingredients.edit', $ingredient) }}" class="btn btn-sm btn-primary-600">Edit Ingredient</a>
                <a href="{{ route('admin.ecommerce.ingredients.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
            </div>
        </div>
        <div class="card-body p-24">
            <div class="row g-24">
                <div class="col-xl-4">
                    <div class="border radius-12 p-20 h-100">
                        <h6 class="mb-16">Visual</h6>
                        <div class="d-flex justify-content-center align-items-center bg-light radius-12 p-16">
                            <img src="{{ $ingredient->icon_path ? asset('storage/' . $ingredient->icon_path) : asset('assets/images/logo-icon.png') }}"
                                alt="icon" class="w-140-px h-140-px radius-12 border object-fit-cover" onerror="this.onerror=null;this.src='{{ asset('assets/images/logo-icon.png') }}';">
                        </div>
                    </div>
                </div>
                <div class="col-xl-8">
                    <div class="border radius-12 p-20 h-100">
                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-20">
                            <div>
                                <h4 class="mb-8">{{ $ingredient->main_heading }}</h4>
                                <p class="mb-0 text-secondary-light">{{ $ingredient->short_heading }}</p>
                            </div>
                            @if($ingredient->is_active)
                                <span class="badge bg-success-100 text-success-600 px-12 py-6 fw-semibold">Active</span>
                            @else
                                <span class="badge bg-danger-100 text-danger-600 px-12 py-6 fw-semibold">Inactive</span>
                            @endif
                        </div>

                        <div class="row g-16">
                            <div class="col-md-6">
                                <div class="p-12 radius-8 bg-light">
                                    <p class="text-secondary-light mb-6 text-sm">Category</p>
                                    <p class="mb-0 fw-semibold">{{ $ingredient->category?->name ?? 'Uncategorized' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-12 radius-8 bg-light">
                                    <p class="text-secondary-light mb-6 text-sm">Record ID</p>
                                    <p class="mb-0 fw-semibold">#{{ $ingredient->id }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="border radius-12 p-20">
                        <h6 class="mb-12">Description</h6>
                        <p class="mb-0 text-secondary-light lh-lg">{{ $ingredient->description ?: 'N/A' }}</p>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="border radius-12 p-20 h-100">
                        <h6 class="mb-16">Formula & Dosage Information</h6>
                        <div class="d-flex flex-column gap-12">
                            <div class="p-12 radius-8 bg-light">
                                <p class="text-secondary-light mb-6 text-sm">Formula / Scientific Name</p>
                                <p class="mb-0 fw-semibold">{{ $ingredient->dosage_heading_one ?: 'N/A' }}</p>
                            </div>
                            <div class="p-12 radius-8 bg-light">
                                <p class="text-secondary-light mb-6 text-sm">Dosage Amount</p>
                                <p class="mb-0 fw-semibold">{{ $ingredient->dosage_heading_two ?: 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="border radius-12 p-20 h-100">
                        <h6 class="mb-16">Benefits Tabs Headings</h6>
                        <div class="d-flex flex-wrap gap-8">
                            @forelse ($ingredient->benefits as $benefit)
                                <span class="badge bg-primary-100 text-primary-700 px-12 py-6">{{ $benefit->heading }}</span>
                            @empty
                                <span class="text-secondary-light">No benefits added.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
