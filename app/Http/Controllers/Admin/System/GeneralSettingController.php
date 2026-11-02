<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\UpdateAuthSettingsRequest;
use App\Http\Requests\Admin\System\UpdateGeneralSettingsRequest;
use App\Http\Requests\Admin\System\UpdatePaymentGatewaySettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GeneralSettingController extends Controller
{
    public function index(): View
    {
        $settings = [
            'site_name' => Setting::get('site_name', 'NutriBuddy'),
            'company_name' => Setting::get('company_name', Setting::get('site_name', 'NutriBuddy Kids Pvt. Ltd.')),
            'meta_description' => Setting::get('meta_description', 'Your Health Partner - Premium Nutrition & Wellness Store'),
            'meta_keywords' => Setting::get('meta_keywords', 'nutrition, wellness, health, supplements'),
            'contact_email' => Setting::get('contact_email', 'admin@nutribuddy.com'),
            'contact_phone' => Setting::get('contact_phone', '+91 9876543210'),
            'address' => Setting::get('address', '123 Health St, Wellness City'),
            'website_url' => Setting::get('website_url', config('app.url', 'https://nutribuddy.in')),
            'gst_number' => Setting::get('gst_number', ''),
            'pan_number' => Setting::get('pan_number', ''),
            'cin_number' => Setting::get('cin_number', ''),
            'store_state' => Setting::get('store_state', 'Gujarat'),
        ];

        $states = app(\App\Services\Checkout\StateCityService::class)->checkoutStates();

        return view('admin.system.settings.general', compact('settings', 'states'));
    }

    public function update(UpdateGeneralSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        foreach ($validated as $key => $value) {
            Setting::set($key, $value, 'general');
        }

        return back()->with('success', 'Settings updated successfully.');
    }

    public function paymentGateways(): View
    {
        $settings = [
            'razorpay_enabled' => Setting::get('razorpay_enabled', config('services.razorpay.key_id') ? '1' : '0'),
            'razorpay_env' => Setting::get('razorpay_env', 'sandbox'),
            'cashfree_enabled' => Setting::get('cashfree_enabled', config('services.cashfree.app_id') ? '1' : '0'),
            'cashfree_env' => Setting::get('cashfree_env', \App\Services\Payments\CashfreePaymentService::resolveEnvironment()),
            'cod_enabled' => Setting::get('cod_enabled', '1'),
            'cod_otp_enabled' => Setting::get('cod_otp_enabled', '1'),
        ];

        return view('admin.system.settings.payment-gateways', compact('settings'));
    }

    public function updatePaymentGateways(UpdatePaymentGatewaySettingsRequest $request): RedirectResponse
    {
        if ($request->has('gateway') && $request->input('gateway') === 'razorpay') {
            $validated = $request->validated();
            Setting::set('razorpay_enabled', $request->has('razorpay_enabled') ? '1' : '0', 'payment');
            Setting::set('razorpay_env', $validated['razorpay_env'], 'payment');
        } elseif ($request->has('gateway') && $request->input('gateway') === 'cashfree') {
            $validated = $request->validated();
            Setting::set('cashfree_enabled', $request->has('cashfree_enabled') ? '1' : '0', 'payment');
            Setting::set('cashfree_env', $validated['cashfree_env'], 'payment');
        } elseif ($request->has('gateway') && $request->input('gateway') === 'cod') {
            Setting::set('cod_enabled', $request->has('cod_enabled') ? '1' : '0', 'payment');
            Setting::set('cod_otp_enabled', $request->has('cod_otp_enabled') ? '1' : '0', 'payment');
        } else {
            // General bulk update
            Setting::set('razorpay_enabled', $request->has('razorpay_enabled') ? '1' : '0', 'payment');
            Setting::set('razorpay_env', $request->input('razorpay_env', 'sandbox'), 'payment');
            Setting::set('cashfree_enabled', $request->has('cashfree_enabled') ? '1' : '0', 'payment');
            Setting::set('cashfree_env', $request->input('cashfree_env', 'sandbox'), 'payment');
            Setting::set('cod_enabled', $request->has('cod_enabled') ? '1' : '0', 'payment');
            Setting::set('cod_otp_enabled', $request->has('cod_otp_enabled') ? '1' : '0', 'payment');
        }

        return back()->with('success', 'Payment gateway settings updated successfully.');
    }

    public function authSettings(): View
    {
        $settings = [
            'phone_login_enabled'  => Setting::get('phone_login_enabled', '1'),
            'google_login_enabled' => Setting::get('google_login_enabled', '0'),
            'google_client_id'     => Setting::get('google_client_id', ''),
            'google_client_secret' => Setting::get('google_client_secret', ''),
        ];

        return view('admin.system.settings.auth', compact('settings'));
    }

    public function updateAuthSettings(UpdateAuthSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Setting::set('phone_login_enabled', $request->has('phone_login_enabled') ? '1' : '0', 'auth');
        Setting::set('google_login_enabled', $request->has('google_login_enabled') ? '1' : '0', 'auth');
        Setting::set('google_client_id', $validated['google_client_id'] ?? '', 'auth');
        Setting::set('google_client_secret', $validated['google_client_secret'] ?? '', 'auth');

        return back()->with('success', 'Authentication settings updated successfully.');
    }
}

