@extends('layouts.admin')

@php
    $title = 'Create Diet Plan';
    $subTitle = 'Diet Services Management / Create';
@endphp

@section('content')
    @include('admin.partials.messages')

    <div class="card border-0 radius-12 mb-24">
        <div class="card-header bg-base border-bottom py-16 px-24">
            <h5 class="card-title mb-0">Create Diet Plan</h5>
        </div>
        <div class="card-body p-24">
            <form method="POST" action="{{ route('admin.ecommerce.diet-plans.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="name">Name</label>
                    <input id="name" name="name" class="form-control" value="{{ old('name') }}" required maxlength="255">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="slug">Slug</label>
                    <input id="slug" name="slug" class="form-control" value="{{ old('slug') }}" required maxlength="255">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="level">Level</label>
                    <select id="level" name="level" class="form-select" required>
                        @foreach (['basic', 'plus', 'pro', 'premium'] as $level)
                            <option value="{{ $level }}" @selected(old('level') === $level)>{{ ucfirst($level) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="price">Base Price</label>
                    <input id="price" type="number" name="price" min="0" step="0.01" class="form-control" value="{{ old('price', 0) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="monthly_price">Monthly Price</label>
                    <input id="monthly_price" type="number" name="monthly_price" min="0" step="0.01" class="form-control" value="{{ old('monthly_price') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="quarterly_price">Quarterly Price</label>
                    <input id="quarterly_price" type="number" name="quarterly_price" min="0" step="0.01" class="form-control" value="{{ old('quarterly_price') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="annual_price">Annual Price</label>
                    <input id="annual_price" type="number" name="annual_price" min="0" step="0.01" class="form-control" value="{{ old('annual_price') }}">
                </div>
                <div class="col-12">
                    <label class="form-label" for="features">Features (one per line)</label>
                    <textarea id="features" name="features_text" class="form-control" rows="4">{{ old('features_text') }}</textarea>
                </div>
                <div class="col-12">
                    <input type="hidden" name="is_active" value="0">
                    <label class="form-check-label">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', true))>
                        Active
                    </label>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-600">Create Plan</button>
                    <a href="{{ route('admin.ecommerce.diet-plans.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
