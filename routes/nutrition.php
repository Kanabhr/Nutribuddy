<?php

use App\Http\Controllers\Api\Nutrition\QuestionnaireController as QuestionnaireApiController;
use App\Http\Controllers\Frontend\Nutrition\ChildCheckInController;
use App\Http\Controllers\Frontend\Nutrition\MealItemController;
use App\Http\Controllers\Frontend\Nutrition\QuestionnaireController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Nutrition Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('nutrition')->name('nutrition.')->group(function () {
    // Toggle food item (tick/untick)
    Route::post('/toggle-item', [MealItemController::class, 'toggleItem'])->name('toggle-item');

    // Quarterly check-in
    Route::post('/check-in', [ChildCheckInController::class, 'saveCheckIn'])->name('check-in');
});

// Questionnaire
Route::get('/nutrition/questionnaire', [QuestionnaireController::class, 'show'])
    ->name('frontend.nutrition.questionnaire');

Route::get('/nutrition/questionnaire/child/{child}', [QuestionnaireController::class, 'showForChild'])
    ->middleware('auth')
    ->whereNumber('child')
    ->name('frontend.nutrition.questionnaire.child');

Route::view('/personalized-diet-chart', 'frontend.nutrition.diet-chart-overview')
    ->name('frontend.nutrition.diet-chart-overview');

Route::prefix('api/nutrition/questionnaire')->group(function () {
    Route::post('/submit', [QuestionnaireApiController::class, 'submitInitialAnswers'])
        ->name('api.nutrition.questionnaire.submit');
    Route::get('/checkout/auth', function (Request $request) {
        return redirect()->route('frontend.login', $request->query());
    })->name('api.nutrition.questionnaire.checkout.auth');
});

Route::redirect('/child-profile/{child}', '/child-profile')->name('child.profile');
Route::redirect('/child-profile/{child}/diet-chart', '/child-profile')->name('child.diet_chart');
