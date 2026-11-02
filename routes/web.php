<?php

use App\Http\Controllers\CsrfTokenController;
use App\Http\Controllers\Api\Webhooks\AiSensyWebhookController;
use App\Http\Controllers\Api\Webhooks\CashfreePaymentController;
use App\Http\Controllers\Api\Webhooks\RazorpayPaymentController;
use App\Http\Controllers\Api\Webhooks\RazorpayWebhookController;
use App\Http\Controllers\Frontend\Checkout\CheckoutController as FrontendCheckoutController;
use App\Http\Controllers\StorageController;
use Illuminate\Support\Facades\Route;

Route::get('/csrf-token', [CsrfTokenController::class, 'show'])->name('csrf.token');

Route::get('/storage/{path}', [StorageController::class, 'showPublic'])
    ->where('path', '.*')
    ->name('storage.public');

Route::prefix('/checkout')->name('checkout.')->group(function () {
    Route::get('/', [FrontendCheckoutController::class, 'index'])->name('index');
    Route::post('/', [FrontendCheckoutController::class, 'store'])->name('store');
    Route::get('/cities/{stateCode}', [FrontendCheckoutController::class, 'getCities'])
        ->where('stateCode', '[A-Za-z0-9\-]+')
        ->name('cities');
});

Route::prefix('/payment/cashfree')->name('cashfree.')->group(function () {
    Route::get('/return', [CashfreePaymentController::class, 'return'])->name('return');
    Route::post('/webhook', [CashfreePaymentController::class, 'webhook'])->name('webhook');
});

Route::prefix('/payment/razorpay')->name('razorpay.')->group(function () {
    Route::post('/verify', [RazorpayPaymentController::class, 'verify'])->name('verify');
    Route::post('/webhook', [RazorpayWebhookController::class, 'handle'])->name('webhook');
});

Route::post('/webhooks/aisensy', [AiSensyWebhookController::class, 'handle'])->name('aisensy.webhook');

require __DIR__ . '/auth.php';
require __DIR__ . '/admin.php';
require __DIR__ . '/storefront.php';
require __DIR__ . '/user.php';
require __DIR__ . '/nutrition.php';

if (app()->environment('local')) {
    Route::middleware(['auth'])->get('/debug/authorization', function () {
        return view('debug/authorization-check');
    })->name('debug.authorization');
}

require __DIR__ . '/frontend-diet-chart.php';
