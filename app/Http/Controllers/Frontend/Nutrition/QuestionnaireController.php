<?php

namespace App\Http\Controllers\Frontend\Nutrition;

use App\Http\Controllers\Controller;
use App\Models\Nutrition\Child;
use App\Models\Nutrition\QuestionnaireQuestion;
use App\Models\SubscriptionPlan;
use App\Support\PlanAccess;
use Illuminate\Http\Request;

class QuestionnaireController extends Controller
{
    public function show(Request $request, ?Child $child = null)
    {
        $payload = $request->query('payload');

        if ($payload) {
            parse_str(base64_decode($payload), $params);
            $planSlug = $params['plan'] ?? null;
            $cycle = $params['cycle'] ?? 'monthly';
        } else {
            $planSlug = $request->query('plan');
            $cycle = $request->query('cycle', 'monthly');
        }

        if (! $planSlug) {
            return redirect()->route('diet_chart')->with('error', 'Please select a plan first.');
        }

        $plan = SubscriptionPlan::where('slug', $planSlug)->firstOrFail();
        $child ??= $request->filled('child_id')
            ? $request->user()?->children()->findOrFail($request->integer('child_id'))
            : null;
        if (! $child && $planSlug !== 'basic' && $request->user()?->children()->count() === 1) {
            $child = $request->user()->children()->first();
        }

        $modelsToFetch = PlanAccess::questionModels($planSlug);

        // For an existing child, only ask questions whose model level is ABOVE the
        // level they were last assessed at – so upgrading Basic → Premium shows only
        // Plus, Pro, and Premium questions (the ones the child has never answered).
        //
        // If the new plan is the same rank or lower than the previous assessment
        // (e.g. re-submitting Basic), or if there happen to be no higher-level
        // questions for the chosen plan, we fall back to showing the full question
        // set for the plan so parents can update all their answers.
        if ($child) {
            $previousSlug = $child->assessments()->latest()->value('model_level');
            $previousRank = PlanAccess::rank($previousSlug);
            $newRank      = PlanAccess::rank($planSlug);

            if ($previousRank > 0 && $newRank > $previousRank) {
                // Collect only the model tiers strictly above the previous assessment.
                $higherModels = array_values(array_filter(
                    PlanAccess::QUESTION_MODELS,
                    fn (int $rank) => $rank > $previousRank && $rank <= $newRank,
                    ARRAY_FILTER_USE_KEY
                ));

                $higherQuestions = QuestionnaireQuestion::whereIn('model', $higherModels)
                    ->orderBy('sort_order')
                    ->get()
                    ->groupBy('section');

                // Only use the narrower set when there are actually higher-level questions.
                if ($higherQuestions->isNotEmpty()) {
                    $questions = $higherQuestions;

                    // If the child already holds an active paid subscription that covers
                    // this plan (or higher), do NOT initiate another payment — just save
                    // the new answers and redirect to the meal plan.
                    $skipPayment = \App\Models\DietSubscription::query()
                        ->where('user_id', $request->user()->id)
                        ->where('child_id', $child->id)
                        ->usable()
                        ->with('subscriptionPlan')
                        ->get()
                        ->contains(fn ($s) => PlanAccess::rank($s->subscriptionPlan?->slug) >= $newRank);

                    return view('frontend.nutrition.questionnaire', compact('plan', 'cycle', 'questions', 'child', 'skipPayment'));
                }
            }
        }

        // Default: show all questions for the chosen plan (new child, same plan, or
        // downgrade path – the last case is not expected in normal flow).
        $questions = QuestionnaireQuestion::whereIn('model', $modelsToFetch)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('section');

        $skipPayment = false;

        return view('frontend.nutrition.questionnaire', compact('plan', 'cycle', 'questions', 'child', 'skipPayment'));
    }

    public function showForChild(Request $request, Child $child)
    {
        $child = $request->user()->children()->findOrFail($child->id);

        // Only supply a default plan slug when the caller did not provide one.
        // If the request already carries ?plan=premium (an upgrade link), preserve it
        // so the upgrade-filter logic in show() can compare the two ranks correctly.
        if (! $request->filled('plan') && ! $request->filled('payload')) {
            $planSlug = $child->assessments()->latest()->value('model_level');

            if ($planSlug && SubscriptionPlan::where('slug', $planSlug)->exists()) {
                $request->query->set('plan', $planSlug);
            }
        }

        return $this->show($request, $child);
    }
}
