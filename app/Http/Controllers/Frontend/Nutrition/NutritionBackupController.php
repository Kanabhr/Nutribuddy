<?php

namespace App\Http\Controllers\Frontend\Nutrition;

use App\Http\Controllers\Controller;
use App\Services\Nutrition\NutritionPageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NutritionBackupController extends Controller
{
    public function nutritionBackup(Request $request, NutritionPageService $nutritionPages)
    {
        try {
            $page = $nutritionPages->build((int) $request->user()->id, $request->query('child_id'));
            unset($page['viewData']['dailyTargets']);

            return view('pages.user-panel.nutrition_backup', $page['viewData']);
        } catch (\Exception $exception) {
            Log::error('Nutrition Backup Error: ' . $exception->getMessage());

            return view('pages.user-panel.nutrition_backup');
        }
    }
}
