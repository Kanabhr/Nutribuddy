@extends('layouts.admin')
@php
    $title = 'Edit Diet Plan';
    $subTitle = 'Diet Services Management / Edit';
@endphp

@section('content')
    @include('admin.partials.messages')

    <div class="card border-0 radius-12 mb-24">
        <div class="card-header bg-base border-bottom py-16 px-24">
            <h5 class="card-title mb-0">Edit Plan: {{ $diet_plan->name }}</h5>
        </div>
        <div class="card-body p-24">
            <form method="POST" action="{{ route('admin.ecommerce.diet-plans.update', $diet_plan) }}" class="row g-3">
                @csrf
                @method('PUT')
                
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="f7:layers"></iconify-icon>
                        </span>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $diet_plan->name) }}" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Monthly Price (₹)</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="lucide:indian-rupee"></iconify-icon>
                        </span>
                        <input type="number" step="0.01" name="monthly_price" class="form-control" value="{{ old('monthly_price', $diet_plan->monthly_price) }}">
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Quarterly Price (₹)</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="lucide:indian-rupee"></iconify-icon>
                        </span>
                        <input type="number" step="0.01" name="quarterly_price" class="form-control" value="{{ old('quarterly_price', $diet_plan->quarterly_price) }}">
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Annual Price (₹)</label>
                    <div class="icon-field">
                        <span class="icon">
                            <iconify-icon icon="lucide:indian-rupee"></iconify-icon>
                        </span>
                        <input type="number" step="0.01" name="annual_price" class="form-control" value="{{ old('annual_price', $diet_plan->annual_price) }}">
                    </div>
                </div>

                <div class="col-md-12 mt-16">
                    <div class="form-check form-switch d-flex align-items-center gap-2 p-0 mb-8">
                        <input class="form-check-input m-0 float-none" type="checkbox" value="1" name="is_active" {{ $diet_plan->is_active ? 'checked' : '' }} id="edit_is_active">
                        <label class="form-check-label m-0" for="edit_is_active">Active Status</label>
                    </div>
                </div>

                <div class="col-12 mt-24">
                    <button type="submit" class="btn btn-primary-600">Update Diet Plan</button>
                    <a href="{{ route('admin.ecommerce.diet-plans.index') }}" class="btn btn-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
