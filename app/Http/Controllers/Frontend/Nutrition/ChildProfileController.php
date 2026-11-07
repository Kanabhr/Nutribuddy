<?php

namespace App\Http\Controllers\Frontend\Nutrition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\CreateChildProfileRequest;
use App\Models\DietSubscription;
use App\Models\Nutrition\DietPlan;
use App\Services\Nutrition\ChildProfileService;
use App\Services\Nutrition\DietChartDisplayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChildProfileController extends Controller
{
    /**
     * Gap categories as stored by the diet engine, with their sort rank, the CSS
     * class the profile page already styles, and the badge text.
     */
    private const GAP_BADGES = [
        'Critical gap'    => [0, 'crit', 'Critical'],
        'Significant gap' => [1, 'gap', 'Gap'],
        'Partial gap'     => [2, 'part', 'Partial'],
        'Met'             => [3, 'met', 'Met'],
    ];

    public function userPanelShow(Request $request)
    {
        $child = $request->user()->children()->latest()->first();
        if (! $child) {
            return redirect()->route('child-profile.create');
        }

        $assessment = $child->assessments()->latest()->first();

        // The family only sees active + approved plans (same rule as the nutrition
        // and meal-plan pages). A chart still under review shows no gaps here.
        $dietPlan = DietPlan::whereHas('assessment', fn ($query) => $query->where('child_id', $child->id))
            ->where('status', 'active')
            ->where('review_status', 'approved')
            ->latest()
            ->first();
        $nutrientGaps = $this->nutrientGapRows($dietPlan);

        $subscription = DietSubscription::with('subscriptionPlan')
            ->where('child_id', $child->id)->where('payment_status', 'paid')->latest()->first();

        return view(
            'frontend.nutrition.child-profile.profile',
            compact('child', 'dietPlan', 'assessment', 'subscription', 'nutrientGaps')
        );
    }

    /**
     * The child's own nutrient gaps from the approved plan, most serious first.
     * Returns an empty array when there is no approved plan or no gap data.
     *
     * @return array<int, array{label: string, class: string, badge: string, percent: float}>
     */
    private function nutrientGapRows(?DietPlan $dietPlan): array
    {
        if (! $dietPlan) {
            return [];
        }

        $planData = $dietPlan->plan_data;
        if (is_string($planData)) {
            $planData = json_decode($planData, true);
        }
        $gaps = DietChartDisplayService::summarizeGaps(is_array($planData) ? ($planData['nutrient_gaps'] ?? []) : []);

        $rows = [];
        foreach ($gaps as $nutrient => $gap) {
            if (! is_array($gap)) {
                continue;
            }

            $category = (string) ($gap['gap_category'] ?? '');
            [$rank, $class, $badge] = self::GAP_BADGES[$category] ?? [4, 'part', $category !== '' ? $category : 'Unknown'];

            $rows[] = [
                'rank'    => $rank,
                'label'   => (string) Str::of((string) $nutrient)
                    ->replaceMatches('/_(kcal|mg|g|mcg|ug|iu)$/i', '')
                    ->replace('_', ' ')
                    ->title(),
                'class'   => $class,
                'badge'   => $badge,
                'percent' => round((float) ($gap['adequacy_percent'] ?? 0), 1),
            ];
        }

        usort($rows, fn ($a, $b) => [$a['rank'], $a['label']] <=> [$b['rank'], $b['label']]);

        return array_map(fn ($row) => array_diff_key($row, ['rank' => true]), $rows);
    }

    public function create()
    {
        if (request()->user()->children()->exists()) {
            return redirect()->route('child-profile');
        }

        return view('frontend.nutrition.child-profile.create');
    }

    public function store(CreateChildProfileRequest $request, ChildProfileService $profiles): JsonResponse
    {
        try {
            ['child' => $child, 'assessment' => $assessment] = $profiles->createProfile(
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Child profile created successfully!',
                'child_id' => $child->id,
                'assessment_id' => $assessment->id,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Child profile creation failed.', ['user_id' => $request->user()->id, 'exception' => $exception]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to create the profile. Please try again.',
            ], 500);
        }
    }
}