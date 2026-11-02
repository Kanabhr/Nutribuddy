@extends('layouts.admin')
@php
    $title = 'Payment Gateways';
    $subTitle = 'Manage payment options, sandbox environments, and live credentials';
@endphp

@section('content')
<div class="row g-4" style="font-family: 'DM Sans', sans-serif;">

    <!-- Razorpay Card -->
    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <form action="{{ route('admin.ecommerce.settings.payment-gateways.update') }}" method="POST">
                @csrf
                <input type="hidden" name="gateway" value="razorpay">
                
                <div class="card-header d-flex justify-content-between align-items-center py-20 px-24 bg-white border-bottom" style="border-bottom-color: #f1f2f4 !important;">
                    <h5 class="card-title mb-0" style="color: #063c36; font-weight: 700; font-size: 1.15rem; letter-spacing: -0.2px;">Razorpay</h5>
                    <div class="form-check form-switch p-0 m-0 d-flex align-items-center">
                        <input class="form-check-input m-0" type="checkbox" role="switch" name="razorpay_enabled" value="1" id="razorpay_enabled" {{ $settings['razorpay_enabled'] == '1' ? 'checked' : '' }} style="width: 46px; height: 23px; cursor: pointer;">
                    </div>
                </div>

                <div class="card-body p-24 bg-white">
                    <div class="mb-24">
                        <label class="form-label d-block mb-12" style="color: #11a086; font-weight: 600; font-size: 0.92rem; letter-spacing: 0.1px;">Environment <span class="text-danger">*</span></label>
                        <div class="d-flex gap-4">
                            <div class="form-check d-flex align-items-center gap-8 pe-4">
                                <input class="form-check-input m-0" type="radio" name="razorpay_env" value="sandbox" id="rzp_sandbox" {{ $settings['razorpay_env'] === 'sandbox' ? 'checked' : '' }} style="cursor: pointer; width: 19px; height: 19px;">
                                <label class="form-check-label ps-2" for="rzp_sandbox" style="font-weight: 500; color: #063c36; cursor: pointer; font-size: 0.95rem;">Sandbox</label>
                            </div>
                            <div class="form-check d-flex align-items-center gap-8 ps-4">
                                <input class="form-check-input m-0" type="radio" name="razorpay_env" value="production" id="rzp_production" {{ $settings['razorpay_env'] === 'production' ? 'checked' : '' }} style="cursor: pointer; width: 19px; height: 19px;">
                                <label class="form-check-label ps-2" for="rzp_production" style="font-weight: 500; color: #063c36; cursor: pointer; font-size: 0.95rem;">Production</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn w-100 py-12 text-white border-0" style="background-color: #063c36; border-radius: 8px; font-weight: 600; font-size: 0.95rem; transition: background-color 0.2s;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cashfree Card -->
    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <form action="{{ route('admin.ecommerce.settings.payment-gateways.update') }}" method="POST">
                @csrf
                <input type="hidden" name="gateway" value="cashfree">
                
                <div class="card-header d-flex justify-content-between align-items-center py-20 px-24 bg-white border-bottom" style="border-bottom-color: #f1f2f4 !important;">
                    <h5 class="card-title mb-0" style="color: #063c36; font-weight: 700; font-size: 1.15rem; letter-spacing: -0.2px;">Cashfree</h5>
                    <div class="form-check form-switch p-0 m-0 d-flex align-items-center">
                        <input class="form-check-input m-0" type="checkbox" role="switch" name="cashfree_enabled" value="1" id="cashfree_enabled" {{ $settings['cashfree_enabled'] == '1' ? 'checked' : '' }} style="width: 46px; height: 23px; cursor: pointer;">
                    </div>
                </div>

                <div class="card-body p-24 bg-white">
                    <div class="mb-24">
                        <label class="form-label d-block mb-12" style="color: #11a086; font-weight: 600; font-size: 0.92rem; letter-spacing: 0.1px;">Environment <span class="text-danger">*</span></label>
                        <div class="d-flex gap-4">
                            <div class="form-check d-flex align-items-center gap-8 pe-4">
                                <input class="form-check-input m-0" type="radio" name="cashfree_env" value="sandbox" id="cf_sandbox" {{ $settings['cashfree_env'] === 'sandbox' ? 'checked' : '' }} style="cursor: pointer; width: 19px; height: 19px;">
                                <label class="form-check-label ps-2" for="cf_sandbox" style="font-weight: 500; color: #063c36; cursor: pointer; font-size: 0.95rem;">Sandbox</label>
                            </div>
                            <div class="form-check d-flex align-items-center gap-8 ps-4">
                                <input class="form-check-input m-0" type="radio" name="cashfree_env" value="production" id="cf_production" {{ $settings['cashfree_env'] === 'production' ? 'checked' : '' }} style="cursor: pointer; width: 19px; height: 19px;">
                                <label class="form-check-label ps-2" for="cf_production" style="font-weight: 500; color: #063c36; cursor: pointer; font-size: 0.95rem;">Production</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn w-100 py-12 text-white border-0" style="background-color: #063c36; border-radius: 8px; font-weight: 600; font-size: 0.95rem; transition: background-color 0.2s;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cash on Delivery (COD) Card -->
    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <form action="{{ route('admin.ecommerce.settings.payment-gateways.update') }}" method="POST">
                @csrf
                <input type="hidden" name="gateway" value="cod">
                
                <div class="card-header d-flex justify-content-between align-items-center py-20 px-24 bg-white border-bottom" style="border-bottom-color: #f1f2f4 !important;">
                    <h5 class="card-title mb-0" style="color: #063c36; font-weight: 700; font-size: 1.15rem; letter-spacing: -0.2px;">Cash on Delivery (COD)</h5>
                    <div class="form-check form-switch p-0 m-0 d-flex align-items-center">
                        <input class="form-check-input m-0" type="checkbox" role="switch" name="cod_enabled" value="1" id="cod_enabled" {{ $settings['cod_enabled'] == '1' ? 'checked' : '' }} style="width: 46px; height: 23px; cursor: pointer;">
                    </div>
                </div>

                <div class="card-body p-24 bg-white d-flex flex-column justify-content-between">
                    <div class="mb-24">
                        <div class="mb-20 p-16 rounded-3" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <label for="cod_otp_enabled" class="form-label mb-1 fw-bold" style="color: #063c36; font-size: 0.95rem; cursor: pointer;">Require OTP Verification</label>
                                    <p class="mb-0 text-muted" style="font-size: 0.85rem;">Sends a 6-digit verification code to the customer's phone before confirming a COD order to prevent RTO & fake orders.</p>
                                </div>
                                <div class="form-check form-switch p-0 m-0 ms-3 d-flex align-items-center">
                                    <input class="form-check-input m-0" type="checkbox" role="switch" name="cod_otp_enabled" value="1" id="cod_otp_enabled" {{ ($settings['cod_otp_enabled'] ?? '1') == '1' ? 'checked' : '' }} style="width: 42px; height: 21px; cursor: pointer;">
                                </div>
                            </div>
                        </div>
                        <p class="mb-0" style="color: #626c72; line-height: 1.5; font-size: 0.95rem;">
                            <strong style="color: #063c36;">Note:</strong> Enable this option to allow customers to pay cash on delivery. When OTP verification is enabled, customers must verify their phone number via OTP to place a COD order.
                        </p>
                    </div>
                    <button type="submit" class="btn w-100 py-12 text-white border-0" style="background-color: #063c36; border-radius: 8px; font-weight: 600; font-size: 0.95rem; transition: background-color 0.2s;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

</div>

<style>
    /* Styling overrides for form-switch and checks to match mockup exactly */
    .form-check-input:checked {
        background-color: #3b82f6 !important;
        border-color: #3b82f6 !important;
    }
    .form-switch .form-check-input {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='rgba%280, 0, 0, 0.25%29'/%3e%3c/svg%3e") !important;
    }
    .form-switch .form-check-input:checked {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e") !important;
    }
    .btn:hover {
        opacity: 0.9;
    }
</style>
@endsection
