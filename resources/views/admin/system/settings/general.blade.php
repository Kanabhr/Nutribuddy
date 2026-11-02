@extends('layouts.admin')
@php
    $title = 'General Settings';
    $subTitle = 'Manage site-wide configurations and SEO defaults';
@endphp

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Site Configuration</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.ecommerce.settings.general.update') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Site Name</label>
                            <div class="icon-field">
                                <span class="icon">
                                    <iconify-icon icon="solar:global-outline"></iconify-icon>
                                </span>
                                <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name']) }}" class="form-control" placeholder="Enter Site Name">
                            </div>
                            @error('site_name')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Legal Company Name (for Invoices)</label>
                            <div class="icon-field">
                                <span class="icon">
                                    <iconify-icon icon="solar:buildings-outline"></iconify-icon>
                                </span>
                                <input type="text" name="company_name" value="{{ old('company_name', $settings['company_name'] ?? '') }}" class="form-control" placeholder="e.g. NutriBuddy Kids Pvt. Ltd.">
                            </div>
                            <p class="text-xs text-secondary-light mt-1">Legal entity name printed on tax invoices.</p>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Contact Email</label>
                            <div class="icon-field">
                                <span class="icon">
                                    <iconify-icon icon="mage:email"></iconify-icon>
                                </span>
                                <input type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email']) }}" class="form-control" placeholder="admin@nutribuddy.com">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Contact Phone</label>
                            <div class="icon-field">
                                <span class="icon">
                                    <iconify-icon icon="solar:phone-outline"></iconify-icon>
                                </span>
                                <input type="text" name="contact_phone" value="{{ old('contact_phone', $settings['contact_phone']) }}" class="form-control" placeholder="+91 XXXX XXXX">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Website URL</label>
                            <div class="icon-field">
                                <span class="icon">
                                    <iconify-icon icon="solar:link-outline"></iconify-icon>
                                </span>
                                <input type="text" name="website_url" value="{{ old('website_url', $settings['website_url'] ?? '') }}" class="form-control" placeholder="https://nutribuddy.in">
                            </div>
                            <p class="text-xs text-secondary-light mt-1">Printed on invoices and footers.</p>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Store / Supplier State (Place of Supply)</label>
                            <div class="icon-field">
                                <span class="icon">
                                    <iconify-icon icon="solar:map-point-outline"></iconify-icon>
                                </span>
                                <select name="store_state" class="form-select form-control">
                                    @foreach($states as $st)
                                        <option value="{{ $st }}" {{ old('store_state', $settings['store_state']) === $st ? 'selected' : '' }}>{{ $st }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="text-xs text-secondary-light mt-1">Orders within this state apply CGST + SGST (e.g. 2.5% + 2.5%). Other states apply IGST (e.g. 5%).</p>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">GST Number / GSTIN</label>
                            <div class="icon-field">
                                <span class="icon">
                                    <iconify-icon icon="lucide:file-text"></iconify-icon>
                                </span>
                                <input type="text" name="gst_number" value="{{ old('gst_number', $settings['gst_number']) }}" class="form-control" placeholder="e.g. 24AAAAA0000A1Z5">
                            </div>
                            <p class="text-xs text-secondary-light mt-1">Leave blank if unregistered.</p>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">PAN Number</label>
                            <div class="icon-field">
                                <span class="icon">
                                    <iconify-icon icon="lucide:credit-card"></iconify-icon>
                                </span>
                                <input type="text" name="pan_number" value="{{ old('pan_number', $settings['pan_number'] ?? '') }}" class="form-control" placeholder="e.g. AAAAA0000A">
                            </div>
                            <p class="text-xs text-secondary-light mt-1">Company PAN for tax invoice.</p>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">CIN Number</label>
                            <div class="icon-field">
                                <span class="icon">
                                    <iconify-icon icon="lucide:hash"></iconify-icon>
                                </span>
                                <input type="text" name="cin_number" value="{{ old('cin_number', $settings['cin_number'] ?? '') }}" class="form-control" placeholder="e.g. U74999GJ2020PTC123456">
                            </div>
                            <p class="text-xs text-secondary-light mt-1">Corporate Identification Number.</p>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Registered Office Address</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Full registered office address...">{{ old('address', $settings['address']) }}</textarea>
                        </div>

                        <hr class="my-4">

                        <div class="col-12">
                            <h6 class="mb-3">Global SEO Defaults</h6>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Default Meta Description</label>
                            <textarea name="meta_description" class="form-control" rows="3" placeholder="This shows when a page doesn't have its own description">{{ old('meta_description', $settings['meta_description']) }}</textarea>
                            <p class="text-xs text-secondary-light mt-8">Recommended: 150-160 characters. Used for Home, Contact, and other generic pages.</p>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Default Meta Keywords</label>
                            <div class="icon-field">
                                <span class="icon">
                                    <iconify-icon icon="lucide:hash"></iconify-icon>
                                </span>
                                <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $settings['meta_keywords']) }}" class="form-control" placeholder="keyword1, keyword2, keyword3">
                            </div>
                            <p class="text-xs text-secondary-light mt-8">Separated by commas.</p>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary-600 px-32">Save All Settings</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
