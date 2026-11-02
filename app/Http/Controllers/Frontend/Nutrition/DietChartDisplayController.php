<?php

namespace App\Http\Controllers\Frontend\Nutrition;

use App\Http\Controllers\Controller;
use App\Models\Nutrition\Child;
use App\Services\Nutrition\DietChartDisplayService;
use App\Services\Nutrition\DietChartGeneratorService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DietChartDisplayController extends Controller
{
    public function __construct(
        private readonly DietChartDisplayService $chartDisplay,
        private readonly DietChartGeneratorService $dietChartGenerator,
    ) {}

    public function show(Request $request, Child $child)
    {
        $child = $request->user()->children()->findOrFail($child->id);

        try {
            $this->chartDisplay->authorizeChildAccess($child);

            $assessment = $child->assessments()->latest()->first();

            if (! $assessment) {
                return view('frontend.nutrition.child-profile.no-assessment', [
                    'child' => $child,
                    'message' => 'No health assessment found. Please complete the questionnaire first.',
                ]);
            }

            $dietPlan = $assessment->dietPlans()->where('status', 'active')->where('review_status', 'approved')->latest()->first();
            $purchasedPlans = $this->chartDisplay->getUserPurchasedPlansCount($child);

            if (! $dietPlan) {
                return view('child-profile.diet-chart-display', [
                    'child' => $child,
                    'assessment' => $assessment,
                    'hasPlan' => false,
                    'purchasedPlans' => $purchasedPlans,
                    'sevenDayPlan' => [],
                    'nutrientGaps' => [],
                    'clinicalTips' => [],
                ]);
            }

            $planData = $dietPlan->plan_data;
            if (is_string($planData)) {
                $planData = json_decode($planData, true) ?? [];
            }
            if (! is_array($planData)) {
                $planData = [];
            }

            $sevenDayPlan = $this->chartDisplay->formatSevenDayPlan($dietPlan, $assessment);
            $nutrientGaps = $planData['nutrient_gaps'] ?? [];
            $clinicalTips = $this->chartDisplay->getClinicalTips($assessment, $nutrientGaps);

            return view('child-profile.diet-chart-display', [
                'child' => $child,
                'assessment' => $assessment,
                'dietPlan' => $dietPlan,
                'hasPlan' => true,
                'purchasedPlans' => $purchasedPlans,
                'sevenDayPlan' => $sevenDayPlan,
                'nutrientGaps' => $nutrientGaps,
                'clinicalTips' => $clinicalTips,
                'dailyNutrients' => $planData['daily_nutrients'] ?? [],
            ]);

        } catch (AuthorizationException $e) {
            Log::warning('Unauthorized diet chart access attempt', [
                'user_id' => Auth::id(),
                'child_id' => $child->id ?? null,
                'child_user_id' => $child->user_id ?? null,
            ]);

            return redirect()->route('child-profile')->with('error', 'Unauthorized access. This child profile does not belong to you.');
        } catch (\Exception $e) {
            Log::error('Diet Chart Display Error: '.$e->getMessage(), [
                'user_id' => Auth::id(),
                'child_id' => $child->id ?? null,
                'exception' => get_class($e),
            ]);

            return redirect()->route('child-profile')->with('error', 'Error loading diet plan');
        }
    }

    public function regenerate(Request $request, Child $child)
    {
        $child = $request->user()->children()->findOrFail($child->id);

        try {
            $this->chartDisplay->authorizeChildAccess($child);

            $assessment = $child->assessments()->latest()->first();

            if (! $assessment) {
                return redirect()->route('child-profile')->with('error', 'No assessment found');
            }

            $purchasedPlans = $this->chartDisplay->getUserPurchasedPlansCount($child);

            if ($purchasedPlans <= 0) {
                return redirect()->route('child-profile')
                    ->with('error', 'No plans remaining. Purchase more plans to regenerate.');
            }

            $answers = $assessment->answers()
                ->pluck('answer_value', 'question_id')
                ->map(function ($value) {
                    return json_decode($value, true);
                })
                ->toArray();

            $consumedSubscriptionId = $this->chartDisplay->decrementPurchasedPlans($child);
            if (! $consumedSubscriptionId) {
                return redirect()->route('child-profile')
                    ->with('error', 'No plans remaining. Purchase more plans to regenerate.');
            }
            $quotaConsumed = true;

            $result = $this->dietChartGenerator->generateDietChart($assessment, $answers);

            if ($result['success']) {
                $quotaConsumed = false;

                return redirect()->route('child-profile')
                    ->with('success', 'Diet plan regenerated successfully!');
            } else {
                $this->chartDisplay->restorePurchasedPlan($consumedSubscriptionId);
                $quotaConsumed = false;

                return redirect()->route('child-profile')
                    ->with('error', 'Failed to regenerate diet plan: '.($result['message'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            if (isset($consumedSubscriptionId) && ($quotaConsumed ?? false)) {
                $this->chartDisplay->restorePurchasedPlan($consumedSubscriptionId);
            }
            Log::error('Diet Chart Regeneration Error: '.$e->getMessage());

            return redirect()->route('child-profile')
                ->with('error', 'Error regenerating diet plan');
        }
    }
}
