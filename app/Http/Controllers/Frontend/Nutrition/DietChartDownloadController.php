<?php

namespace App\Http\Controllers\Frontend\Nutrition;

use App\Http\Controllers\Controller;
use App\Models\Nutrition\Child;
use App\Services\Nutrition\DietChartDisplayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DietChartDownloadController extends Controller
{
    public function __construct(private readonly DietChartDisplayService $chartDisplay) {}

    public function downloadPdf(Request $request, Child $child)
    {
        $child = $request->user()->children()->findOrFail($child->id);

        try {
            $this->chartDisplay->authorizeChildAccess($child);
            $assessment = $child->assessments()->latest()->first();
            $dietPlan = $assessment?->dietPlans()->where('status', 'active')->latest()->first();

            if (! $dietPlan) {
                return redirect()->back()->with('error', 'No active diet plan found');
            }

            return redirect()->back()->with('info', 'PDF generation coming soon!');
        } catch (\Exception $e) {
            Log::error('PDF Download Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error downloading PDF');
        }
    }
}
