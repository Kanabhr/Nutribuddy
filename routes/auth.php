<?php

use App\Http\Controllers\Admin\Access\AuthenticationController;
use App\Http\Controllers\Frontend\Account\AuthController as FrontendAuthController;
use App\Http\Controllers\Frontend\Account\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('authentication')->group(function () {
    Route::controller(AuthenticationController::class)->group(function () {
        Route::get('/forgotpassword', 'forgotPassword')->name('forgotPassword');
        Route::get('/signin', 'signin')->name('signin');
        Route::post('/login', 'login')->middleware('throttle:admin-login')->name('admin.login.post');
        Route::post('/logout', 'logout')->name('admin.logout')->middleware('auth:admin');
        Route::get('/signup', 'signup')->name('signup');
    });
});

Route::name('frontend.')->group(function () {
    Route::controller(FrontendAuthController::class)->group(function () {
        Route::get('/login', 'showLogin')->name('login');
        Route::post('/send-otp', 'sendOtp')->middleware('throttle:otp-send')->name('sendOtp');
        Route::post('/verify-otp', 'verifyOtp')->middleware('throttle:otp-verify')->name('verifyOtp');
        Route::post('/logout', 'logout')->name('logout');
    });

    // Google OAuth
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
});
