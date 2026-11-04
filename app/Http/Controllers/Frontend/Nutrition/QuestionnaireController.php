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

        // We will fetch questions dynamically on the frontend via API,
        // or we can pass them here. Let's pass them here for easier Blade rendering.

        $modelsToFetch = PlanAccess::questionModels($planSlug);

        // An existing child only answers what they have not answered yet (e.g. Basic -> Premium
        // must answer Plus + Pro + Premium questions, not only Premium questions).
        $answeredIds = $child
            ? ($child->assessments()->latest()->first()?->answers()->pluck('question_id')->all() ?? [])
            : [];

        $questions = QuestionnaireQuestion::whereIn('model', $modelsToFetch)
            ->whereNotIn('id', $answeredIds)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('section');

        return view('frontend.nutrition.questionnaire', compact('plan', 'cycle', 'questions', 'child'));
    }

    public function showForChild(Request $request, Child $child)
    {
        $child = $request->user()->children()->findOrFail($child->id);
        $planSlug = $child->assessments()->latest()->value('model_level');

        if ($planSlug && SubscriptionPlan::where('slug', $planSlug)->exists()) {
            $request->query->set('plan', $planSlug);
        }

        return $this->show($request, $child);
    }
}
