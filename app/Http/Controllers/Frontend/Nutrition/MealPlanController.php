<?php

namespace App\Http\Controllers\Frontend\Nutrition;

use App\Http\Controllers\Controller;
use App\Services\Nutrition\NutritionPageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MealPlanController extends Controller
{
    public function mealPlan(Request $request, NutritionPageService $nutritionPages)
    {
        try {
            $viewData = $nutritionPages->buildMealPlan(
                (int) $request->user()->id,
                $request->query('child_id')
            );

            return view('pages.user-panel.meal-plan', $viewData ?? []);
        } catch (\Exception $exception) {
            Log::error('Meal Plan Error: ' . $exception->getMessage());

            return view('pages.user-panel.meal-plan');
        }
    }
}
