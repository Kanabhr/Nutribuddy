<?php

use App\Http\Controllers\Frontend\Checkout\CheckoutController as FrontendCheckoutController;
use App\Http\Controllers\Frontend\Storefront\BlogController;
use App\Http\Controllers\Frontend\Storefront\ContactController as FrontendContactController;
use App\Http\Controllers\Frontend\Storefront\HomeController;
use App\Http\Controllers\Frontend\Storefront\NewsletterSubscriberController as FrontendNewsletterSubscriberController;
use App\Http\Controllers\Frontend\Storefront\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::view('/about', 'frontend.storefront.pages.about-us')->name('about');
Route::view('/ingredients', 'frontend.storefront.pages.ingredients')->name('ingredients');
Route::get('/product', [ProductController::class, 'index'])->name('product');
Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('/diet-chart', function () {
    $plans = \App\Models\SubscriptionPlan::where('is_active', true)->get()->keyBy('slug');
    $hasActivePlan = false;
    if (auth()->check()) {
        $hasActivePlan = \App\Models\DietSubscription::where('user_id', auth()->id())
            ->whereIn('status', ['active', 'completed'])
            ->whereIn('payment_status', ['paid', 'free'])
            ->exists();
    }
    return view('frontend.nutrition.plan-selection', compact('plans', 'hasActivePlan'));
})->name('diet_chart');

Route::middleware('auth')->group(function () {
    Route::post('/diet-chart/payment/create', [\App\Http\Controllers\Frontend\Nutrition\DietPaymentController::class, 'createOrder'])->name('diet.payment.create');
    Route::post('/diet-chart/payment/verify', [\App\Http\Controllers\Frontend\Nutrition\DietPaymentController::class, 'verifyPayment'])->name('diet.payment.verify');
});
Route::get('/diet-chart/payment/cashfree/return', [\App\Http\Controllers\Frontend\Nutrition\DietPaymentController::class, 'handleCashfreeReturn'])->name('diet.payment.cashfree.return');

Route::get('/blog', [BlogController::class, 'index'])->name('blog');
Route::view('/testimonials', 'frontend.storefront.pages.testimonials')->name('testimonials');
Route::get('/blog/{id}', [BlogController::class, 'show'])->name('blog.show');
Route::view('/privacy', 'frontend.storefront.pages.privacy')->name('privacy');
Route::view('/cookies', 'frontend.storefront.pages.cookies')->name('cookies');
Route::view('/return-policy', 'frontend.storefront.pages.return-policy')->name('return-policy');
Route::view('/terms', 'frontend.storefront.pages.terms')->name('terms');
Route::view('/cart', 'frontend.storefront.checkout.cart')->name('cart.page');

Route::get('/contact', [FrontendContactController::class, 'index'])->name('contact');
Route::post('/contact', [FrontendContactController::class, 'store'])->middleware('throttle:form-submit')->name('contact.store');
Route::post('/newsletter/subscribe', [FrontendNewsletterSubscriberController::class, 'store'])->middleware('throttle:form-submit')->name('newsletter.subscribe');

Route::prefix('/guest/checkout')->name('guest.checkout.')->middleware('throttle:checkout')->group(function () {
    Route::post('/summary', [FrontendCheckoutController::class, 'guestSummary'])->name('summary');
});
