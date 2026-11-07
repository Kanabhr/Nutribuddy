<?php

namespace Tests\Feature;

use App\Models\DietSubscription;
use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DietPlan;
use App\Models\Nutrition\DietPlanMeal;
use App\Models\Nutrition\QuestionnaireAnswer;
use App\Models\Nutrition\QuestionnaireQuestion;
use App\Models\NutritionLead;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Nutrition\NutritionLeadReviewService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A3: admin chart review labels days with the plan's real dates (start_date +
 * day_index) instead of Monday.. then "Day 8", and shows the cooking medium
 * from the stored answer when the profile/column value is empty.
 */
class NutritionLeadReviewDaysTest extends TestCase
{
    use RefreshDatabase;

    private function makeLead(array $assessmentAttributes = []): array
    {
        $customer = User::factory()->create();
        $child    = Child::create(['user_id' => $customer->id, 'name' => 'Review Kid', 'sex' => 'girl']);

        $subPlan = SubscriptionPlan::create([
            'name' => 'Plus', 'slug' => 'plus',
            'price' => 999, 'monthly_price' => 999, 'is_active' => true,
        ]);
        $sub = DietSubscription::create([
            'user_id' => $customer->id, 'child_id' => $child->id, 'subscription_plan_id' => $subPlan->id,
            'model' => 'Plus', 'status' => 'active', 'payment_status' => 'paid', 'amount_paid' => 999,
        ]);
        $lead = NutritionLead::create([
            'user_id' => $customer->id, 'child_id' => $child->id,
            'diet_subscription_id' => $sub->id, 'status' => 'pending',
        ]);
        $assessment = ChildAssessment::create(array_merge([
            'child_id'          => $child->id,
            'model_level'       => 'plus',
            'current_weight_kg' => 20,
            'current_height_cm' => 115,
            'diet_type'         => 'vegetarian',
            'meal_pattern'      => '3 meals',
        ], $assessmentAttributes));

        return [$lead, $assessment];
    }

    private function service(): NutritionLeadReviewService
    {
        return app(NutritionLeadReviewService::class);
    }

    public function test_monthly_plan_days_are_labelled_with_real_dates_not_weekday_names_or_day_numbers(): void
    {
        [$lead, $assessment] = $this->makeLead();

        // 2026-11-03 is a Tuesday.
        $plan = DietPlan::create([
            'child_assessment_id' => $assessment->id,
            'status'              => 'pending_review',
            'review_status'       => 'pending_review',
            'plan_data'           => ['schedule' => ['start_date' => '2026-11-03', 'days' => 10]],
        ]);
        for ($day = 0; $day < 10; $day++) {
            DietPlanMeal::create([
                'diet_plan_id' => $plan->id,
                'meal_slot'    => 'Breakfast',
                'sort_order'   => $day,
                'day_index'    => $day,
            ]);
        }

        $labels = $this->service()->buildReviewData($lead)['mealsByDay']->keys()->all();

        $this->assertCount(10, $labels);
        $this->assertSame('Tue, 3 Nov', $labels[0]);
        $this->assertSame('Tue, 10 Nov', $labels[7]);   // day 8, not "Day 8"
        $this->assertSame('Thu, 12 Nov', $labels[9]);
        foreach ($labels as $label) {
            $this->assertStringNotContainsString('Day ', $label);
        }
    }

    public function test_old_plan_without_day_index_falls_back_to_position_and_creation_date(): void
    {
        [$lead, $assessment] = $this->makeLead();

        $plan = DietPlan::create([
            'child_assessment_id' => $assessment->id,
            'status'              => 'pending_review',
            'review_status'       => 'pending_review',
        ]);
        // 3 meals per day, 2 days, no day_index and no schedule in plan_data.
        $order = 0;
        foreach ([0, 1] as $unused) {
            foreach (['Breakfast', 'Lunch', 'Dinner'] as $slot) {
                DietPlanMeal::create([
                    'diet_plan_id' => $plan->id,
                    'meal_slot'    => $slot,
                    'sort_order'   => $order++,
                ]);
            }
        }

        $start  = Carbon::parse($plan->fresh()->created_at)->startOfDay();
        $groups = $this->service()->buildReviewData($lead)['mealsByDay'];

        $this->assertSame(
            [$start->format('D, j M'), $start->copy()->addDay()->format('D, j M')],
            $groups->keys()->all()
        );
        $this->assertCount(3, $groups->first());
    }

    public function test_cooking_medium_falls_back_to_the_stored_answer(): void
    {
        [$lead, $assessment] = $this->makeLead(['cooking_medium' => null]);

        $question = QuestionnaireQuestion::create([
            'model'       => '1 Basic',
            'section'     => 'Food habits',
            'question'    => 'Cooking oil / medium at home',
            'answer_type' => 'Text',
            'sort_order'  => 11,
            'is_optional' => true,
        ]);
        QuestionnaireAnswer::create([
            'child_assessment_id' => $assessment->id,
            'question_id'         => $question->id,
            'answer_value'        => 'Mustard oil',
        ]);

        $this->assertSame('Mustard oil', $this->service()->buildReviewData($lead)['cookingMedium']);
    }

    public function test_cooking_medium_column_wins_over_the_answer_and_blank_gives_not_provided(): void
    {
        [$lead, $assessment] = $this->makeLead(['cooking_medium' => 'Ghee']);
        $this->assertSame('Ghee', $this->service()->buildReviewData($lead)['cookingMedium']);

        $assessment->update(['cooking_medium' => '   ']);
        $this->assertSame('Not provided', $this->service()->buildReviewData($lead->fresh())['cookingMedium']);
    }
}