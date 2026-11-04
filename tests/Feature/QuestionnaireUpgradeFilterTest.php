<?php

namespace Tests\Feature;

use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\QuestionnaireQuestion;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for QuestionnaireController::show upgrade-filtering behaviour.
 *
 * Rules under test:
 *   - Basic → Premium: only Plus, Pro, and Premium questions are shown (the child
 *     already answered Basic).
 *   - Basic → Basic (same plan): all 12 Basic questions are shown so parents can
 *     update answers.
 */
class QuestionnaireUpgradeFilterTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────────────────────
    // Fixtures
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Seed the minimal question set used by both tests:
     *  - 12 Basic questions (model '1 Basic', sort_order 1-12)
     *  -  9 Plus  questions (model '2 Plus',  sort_order 13-21)
     *  -  8 Pro   questions (model '3 Pro',   sort_order 22-29)
     *  -  8 Premium questions (model '4 Premium', sort_order 30-37)
     */
    private function seedQuestions(): void
    {
        $models = [
            ['1 Basic',   12, 1],
            ['2 Plus',     9, 13],
            ['3 Pro',      8, 22],
            ['4 Premium',  8, 30],
        ];

        foreach ($models as [$model, $count, $startOrder]) {
            for ($i = 0; $i < $count; $i++) {
                QuestionnaireQuestion::create([
                    'model'       => $model,
                    'section'     => $model.' section',
                    'question'    => "Question #{$model} ".($startOrder + $i),
                    'answer_type' => 'Text',
                    'sort_order'  => $startOrder + $i,
                    'is_optional' => true,
                ]);
            }
        }
    }

    private function seedPlans(): void
    {
        foreach (['basic', 'plus', 'pro', 'premium'] as $slug) {
            SubscriptionPlan::firstOrCreate(['slug' => $slug], [
                'name'     => ucfirst($slug),
                'price'    => 0,
                'is_active'=> true,
            ]);
        }
    }

    private function childWithBasicAssessment(User $user): Child
    {
        $child = Child::create([
            'user_id' => $user->id,
            'name'    => 'TestChild',
            'sex'     => 'boy',
        ]);

        ChildAssessment::create([
            'child_id'          => $child->id,
            'model_level'       => 'basic',   // previous assessment was at Basic
            'current_weight_kg' => 20,
            'current_height_cm' => 110,
            'diet_type'         => 'vegetarian',
        ]);

        return $child;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Tests
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Basic → Premium upgrade: the controller must show only Plus, Pro, and Premium
     * questions (ranks 2, 3, 4) – NOT the 12 Basic questions the child already answered.
     */
    public function test_basic_to_premium_upgrade_shows_only_plus_pro_premium_questions(): void
    {
        $this->seedQuestions();
        $this->seedPlans();

        $user  = User::factory()->create();
        $child = $this->childWithBasicAssessment($user);

        $response = $this->actingAs($user)
            ->get(route('frontend.nutrition.questionnaire.child', $child).'?plan=premium');

        $response->assertOk();

        /** @var \Illuminate\Support\Collection $questions */
        $questions = $response->viewData('questions');

        // Flatten all questions across sections.
        $all = $questions->flatten();

        // Must have the 9 + 8 + 8 = 25 higher-level questions.
        $this->assertCount(25, $all, 'Expected 25 questions (Plus + Pro + Premium) for Basic → Premium.');

        // None of them must belong to '1 Basic'.
        $basicCount = $all->filter(fn ($q) => $q->model === '1 Basic')->count();
        $this->assertSame(0, $basicCount, 'Basic questions must not appear when upgrading from Basic.');

        // All three higher models must be represented.
        $models = $all->pluck('model')->unique()->sort()->values()->all();
        $this->assertSame(['2 Plus', '3 Pro', '4 Premium'], $models);
    }

    /**
     * Basic → Basic (same plan re-submission): the controller must show all 12 Basic
     * questions so parents can update any answer.
     */
    public function test_basic_to_basic_same_plan_shows_all_12_questions(): void
    {
        $this->seedQuestions();
        $this->seedPlans();

        $user  = User::factory()->create();
        $child = $this->childWithBasicAssessment($user);

        $response = $this->actingAs($user)
            ->get(route('frontend.nutrition.questionnaire.child', $child).'?plan=basic');

        $response->assertOk();

        /** @var \Illuminate\Support\Collection $questions */
        $questions = $response->viewData('questions');

        $all = $questions->flatten();

        $this->assertCount(12, $all, 'All 12 Basic questions must be shown when re-submitting the same plan.');

        // All must belong to '1 Basic'.
        $nonBasic = $all->filter(fn ($q) => $q->model !== '1 Basic')->count();
        $this->assertSame(0, $nonBasic, 'Only Basic questions should appear when the plan is unchanged.');
    }
}
