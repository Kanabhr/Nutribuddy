<?php

namespace App\Http\Controllers\Frontend\Nutrition;

use App\Http\Controllers\Controller;
use App\Models\Nutrition\Child;
use App\Services\Nutrition\NutritionPageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NutritionController extends Controller
{
    public function nutrition(Request $request, NutritionPageService $nutritionPages, ?Child $child = null)
    {
        $childId = $child
            ? $request->user()->children()->findOrFail($child->id)->id
            : $request->query('child_id');

        try {
            $page = $nutritionPages->build((int) $request->user()->id, $childId);

            if (! $page['child'] || ! $page['assessment'] || ! $page['dietPlan']) {
                return view('pages.user-panel.nutrition', $page['viewData']);
            }

            return view('pages.user-panel.nutrition_backup', $page['viewData']);
        } catch (\Exception $exception) {
            Log::error('Nutrition Error: '.$exception->getMessage());

            return view('pages.user-panel.nutrition');
        }
    }
}
