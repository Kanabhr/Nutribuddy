<?php

use App\Http\Controllers\Frontend\Account\UserAddressController;
use App\Http\Controllers\Frontend\Account\UserCouponController;
use App\Http\Controllers\Frontend\Account\UserInvoiceController;
use App\Http\Controllers\Frontend\Account\UserOrderController;
use App\Http\Controllers\Frontend\Account\UserProfileController;
use App\Http\Controllers\Frontend\Account\UserReturnController;
use App\Http\Controllers\Frontend\Account\UserSupportTicketController;
use App\Http\Controllers\Frontend\Account\UserWalletController;
use App\Http\Controllers\Frontend\Checkout\CartController as FrontendCartController;
use App\Http\Controllers\Frontend\Checkout\CheckoutController as FrontendCheckoutController;
use App\Http\Controllers\Frontend\Nutrition\AssessmentController;
use App\Http\Controllers\Frontend\Nutrition\ChildProfileController;
use App\Http\Controllers\Frontend\Nutrition\DietSubscriptionInvoiceController;
use App\Http\Controllers\Frontend\Nutrition\MealPlanController;
use App\Http\Controllers\Frontend\Nutrition\NutritionBackupController;
use App\Http\Controllers\Frontend\Nutrition\NutritionController;
use App\Http\Controllers\Frontend\Storefront\ProductReviewController;
use App\Http\Controllers\Frontend\Storefront\SupplementController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::prefix('/user/cart')->name('user.cart.')->middleware('throttle:cart')->group(function () {
        Route::get('/', [FrontendCartController::class, 'index'])->name('index');
        Route::post('/', [FrontendCartController::class, 'store'])->name('store');
        Route::patch('/items/{itemId}', [FrontendCartController::class, 'update'])->name('items.update');
        Route::delete('/items/{itemId}', [FrontendCartController::class, 'destroy'])->name('items.destroy');
    });

    Route::prefix('/user/checkout')->name('user.checkout.')->middleware('throttle:checkout')->group(function () {
        Route::post('/summary', [FrontendCheckoutController::class, 'summary'])->name('summary');
        Route::post('/cod/send-otp', [FrontendCheckoutController::class, 'sendCodOtp'])->name('cod.send-otp');
        Route::post('/place-order', [FrontendCheckoutController::class, 'placeOrder'])->middleware('throttle:order-submit')->name('place-order');
    });

    Route::prefix('/user/addresses')->name('user.addresses.')->group(function () {
        Route::get('/', [UserAddressController::class, 'index'])->name('index');
        Route::get('/create', [UserAddressController::class, 'create'])->name('create');
        Route::post('/', [UserAddressController::class, 'store'])->name('store');
        Route::get('/{address}/edit', [UserAddressController::class, 'edit'])->name('edit');
        Route::patch('/{address}', [UserAddressController::class, 'update'])->name('update');
        Route::patch('/{address}/default', [UserAddressController::class, 'setDefault'])->name('set-default');
        Route::delete('/{address}', [UserAddressController::class, 'destroy'])->name('destroy');
    });

    // User-facing pages use one stable URL prefix; route names stay unchanged for callers.
    Route::prefix('/user/pages')->group(function () {
        Route::view('/change-password', 'frontend.account.change-password')->name('change-password');
        Route::view('/orders', 'frontend.account.orders.index')->name('order');
        Route::get('/personal-info', [UserAddressController::class, 'index'])->name('personal-info');
        Route::get('/coupons', [UserCouponController::class, 'index'])->name('my-coupons');
        Route::get('/subscriptions', function () {
            $user          = auth()->user();
            $subscriptions = $user->dietSubscriptions()->with('child', 'subscriptionPlan', 'product')->latest()->get();

            // Rule 5: find subscriptions where the child still needs to answer the questionnaire.
            $pendingQuestionnaires = $subscriptions
                ->filter(function ($sub) {
                    if (! $sub->child) {
                        return false;
                    }
                    $assessment = $sub->child->assessments()->latest()->first();
                    $planSlug   = $sub->subscriptionPlan?->slug;

                    return \App\Support\PlanAccess::rank($planSlug) > \App\Support\PlanAccess::rank('basic')
                        && ! \App\Support\PlanAccess::assessmentCoversPlan($assessment?->model_level, $planSlug);
                })
                ->map(fn ($sub) => [
                    'subscription' => $sub,
                    'url'          => route('frontend.nutrition.questionnaire.child', $sub->child_id)
                                      . '?plan=' . urlencode((string) $sub->subscriptionPlan?->slug),
                ])
                ->values();

            return view('frontend.account.subscriptions', compact('subscriptions', 'pendingQuestionnaires'));
        })->name('subscription');
        Route::view('/returns', 'frontend.account.returns.show')->name('user-return');
        Route::view('/dashboard', 'frontend.account.dashboard')->name('userdashboard');
        Route::get('/nutrition', [NutritionController::class, 'nutrition'])->name('nutrition.index');
        Route::get('/nutrition/child/{child}', [NutritionController::class, 'nutrition'])->whereNumber('child')->name('nutrition.child');
        Route::get('/nutrition-backup', [NutritionBackupController::class, 'nutritionBackup'])->name('nutrition.backup');
        Route::get('/meal-plan', [MealPlanController::class, 'mealPlan'])->name('meal-plan');
        Route::get('/supplement', [SupplementController::class, 'index'])->name('supplement');
        Route::get('/child-profile', [ChildProfileController::class, 'userPanelShow'])->name('child-profile');
        Route::get('/child-profile/create', [ChildProfileController::class, 'create'])->name('child-profile.create');
        Route::get('/growth-signal', function () {
            $child = auth()->user()->children()->latest()->first();
            $plan = $child?->assessments()->latest()->first()?->dietPlan;

            return view('frontend.nutrition.growth-signal', compact('child', 'plan'));
        })->name('growth-signal');
        Route::view('/check-in', 'frontend.nutrition.check-in')->name('check-in');
        Route::get('/wallet', [UserWalletController::class, 'index'])->name('wallet');
        Route::get('/support-tickets', [UserSupportTicketController::class, 'index'])->name('user.support-tickets');
        Route::get('/support-tickets/{ticket}', [UserSupportTicketController::class, 'show'])->name('user.support-tickets.show');
        Route::get('/reviews', [ProductReviewController::class, 'userIndex'])->name('user.reviews.index');
        Route::get('/invoices', [UserInvoiceController::class, 'index'])->name('user.invoices.index');
        Route::get('/returns/list', [UserReturnController::class, 'index'])->name('user.orders.returns.index');
        Route::get('/orders/{order}/detail', [UserOrderController::class, 'detailPage'])->name('user.orders.detail-page');
        Route::get('/orders/{order}/invoice-page', [UserInvoiceController::class, 'page'])->name('user.orders.invoice-page');
        Route::get('/assessment', [AssessmentController::class, 'index'])->name('user.assessment.index');
        Route::get('/assessment/history', [AssessmentController::class, 'history'])->name('user.assessment.history');
        Route::get('/assessment/compare', [AssessmentController::class, 'compare'])->name('user.assessment.compare');
        Route::get('/assessment/{attempt}/result', [AssessmentController::class, 'result'])->name('user.assessment.result');
        Route::get('/assessment/{attempt}/detail', [AssessmentController::class, 'detail'])->name('user.assessment.detail');
    });

    // Keep existing write, JSON, and download endpoints stable.
    Route::post('/personal-info', [UserProfileController::class, 'update'])->name('personal-info.update');
    Route::post('/child-profile', [ChildProfileController::class, 'store'])->name('child-profile.store');
    Route::get('/diet-subscriptions/{subscription}/invoice-download', [DietSubscriptionInvoiceController::class, 'download'])->name('diet-subscriptions.invoice-download');
    Route::get('/user/orders/returns', fn () => redirect()->route('user.orders.returns.index'))->name('legacy.user.orders.returns');
    Route::get('/user/orders/{order}/detail', fn ($order) => redirect()->route('user.orders.detail-page', $order))->name('legacy.user.orders.detail-page');
    Route::get('/user/orders/{order}/invoice-page', fn ($order) => redirect()->route('user.orders.invoice-page', $order))->name('legacy.user.orders.invoice-page');
    Route::get('/support-tickets/{ticket}', fn ($ticket) => redirect()->route('user.support-tickets.show', $ticket))->name('legacy.user.support-tickets.show');
    Route::get('/user/orders', [UserOrderController::class, 'index'])->name('user.orders.index');
    Route::get('/user/orders/{order}', [UserOrderController::class, 'show'])->name('user.orders.show');
    Route::get('/user/orders/{order}/invoice', [UserInvoiceController::class, 'show'])->name('user.orders.invoice');
    Route::get('/user/orders/{order}/invoice-download', [UserInvoiceController::class, 'download'])->name('user.orders.invoice-download');
    Route::patch('/user/orders/{order}/cancel', [UserOrderController::class, 'cancel'])->name('user.orders.cancel');
    Route::post('/user/orders/{order}/returns', [UserReturnController::class, 'store'])->name('user.orders.returns.store');
    Route::post('/support-tickets', [UserSupportTicketController::class, 'store'])->name('user.support-tickets.store');
    Route::post('/support-tickets/{ticket}/reply', [UserSupportTicketController::class, 'reply'])->name('user.support-tickets.reply');
    Route::post('/product/{product}/reviews', [ProductReviewController::class, 'store'])->name('reviews.store');
    Route::post('/user/assessment', [AssessmentController::class, 'store'])->name('user.assessment.store');

    // Preserve bookmarks to the former menu URLs.
    foreach ([
        '/change-password' => 'change-password', '/order' => 'order', '/personal-info' => 'personal-info',
        '/my-coupons' => 'my-coupons', '/subscription' => 'subscription', '/user-return' => 'user-return',
        '/userdashboard' => 'userdashboard', '/nutrition' => 'nutrition.index', '/nutrition-backup' => 'nutrition.backup', '/meal-plan' => 'meal-plan',
        '/supplement' => 'supplement', '/child-profile' => 'child-profile', '/child-profile/create' => 'child-profile.create',
        '/growth-signal' => 'growth-signal', '/check-in' => 'check-in', '/wallet' => 'wallet',
        '/support-tickets' => 'user.support-tickets', '/user/reviews' => 'user.reviews.index',
        '/user/invoices' => 'user.invoices.index',
        '/user/assessment' => 'user.assessment.index', '/user/assessment/history' => 'user.assessment.history',
        '/user/assessment/compare' => 'user.assessment.compare',
    ] as $legacyPath => $routeName) {
        Route::get($legacyPath, fn () => redirect()->route($routeName))
            ->name('legacy.'.trim(str_replace('/', '.', $legacyPath), '.'));
    }
    Route::get('/user/assessment/{attempt}/result', fn ($attempt) => redirect()->route('user.assessment.result', $attempt))->name('legacy.user.assessment.result');
    Route::get('/user/assessment/{attempt}/detail', fn ($attempt) => redirect()->route('user.assessment.detail', $attempt))->name('legacy.user.assessment.detail');
    Route::get('/nutrition/child/{child}', fn ($child) => redirect()->route('nutrition.child', $child))->whereNumber('child')->name('legacy.nutrition.child');
});
