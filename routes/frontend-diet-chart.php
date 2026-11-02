<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\Nutrition\DietChartDisplayController;
use App\Http\Controllers\Frontend\Nutrition\DietChartDownloadController;

Route::middleware('auth')->prefix('/child/{child}')->group(function () {
    Route::get('/diet-chart', [DietChartDisplayController::class, 'show'])
        ->name('child.diet-chart.show')
        ->where('child', '[0-9]+');

    Route::post('/diet-chart/regenerate', [DietChartDisplayController::class, 'regenerate'])
        ->name('child.diet-chart.regenerate')
        ->where('child', '[0-9]+');

    Route::get('/diet-chart/download-pdf', [DietChartDownloadController::class, 'downloadPdf'])
        ->name('child.diet-chart.download-pdf')
        ->where('child', '[0-9]+');
});
