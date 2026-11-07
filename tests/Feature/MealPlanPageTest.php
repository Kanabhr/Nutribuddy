<?php

namespace Tests\Feature;

use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DietPlan;
use App\Models\Nutrition\DietPlanItem;
use App\Models\Nutrition\DietPlanMeal;
use App\Models\Nutrition\FoodMaster;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B2: the meal-plan page (/user/pages/meal-plan) reads the real child and the
 * real plan: index-keyed days with real dates, week tabs, two-item Lunch/Dinner
 * with portion and kcal, and a "being reviewed" message without an approved plan.
 */
class MealPlanPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeChild(User $parent, string $name = 'Mira'): array
    {
        $child = Child::create([
            'user_id' => $parent->id,
            'name'    => $name,
            'sex'     => 'girl',
            'dob'     => now()->subYears(8)->toDateString(),
        ]);
        $assessment = ChildAssessment::create([
            'child_id'          => $child->id,
            'model_level'       => 'basic',
            'current_weight_kg' => 24,
            'current_height_cm' => 125,
            'diet_type'         => 'vegetarian',
            'meal_pattern'      => '3 meals',
        ]);

        return [$child, $assessment];
    }

    private function makeFood(string $name, float $kcal, string $slot = 'Lunch'): FoodMaster
    {
        \Illuminate\Database\Eloquent\Model::unguard();
        $food = FoodMaster::create([
            'dish_id'         => 'TEST-' . uniqid(),
            'dish_name'       => $name,
            'slot'            => $slot,
            'serving_g'       => 100,
            'energy_kcal'     => $kcal,
            'protein_g'       => 3,
            'carb_g'          => 20,
            'fat_g'           => 2,
            'is_veg'          => 1,
            'is_vegan'        => 0,
            'is_jain_ok'      => 1,
            'contains_dairy'  => 0,
            'contains_gluten' => 0,
            'contains_egg'    => 0,
            'contains_nuts'   => 0,
        ]);
        \Illuminate\Database\Eloquent\Model::reguard();

        return $food;
    }

    private function addMeal(DietPlan $plan, int $dayIndex, string $slot, array $foodsWithPortion, int $order): void
    {
        $meal = DietPlanMeal::create([
            'diet_plan_id' => $plan->id,
            'meal_slot'    => $slot,
            'sort_order'   => $order,
            'day_index'    => $dayIndex,
        ]);
        foreach ($foodsWithPortion as [$food, $portion]) {
            DietPlanItem::create([
                'diet_plan_meal_id' => $meal->id,
                'food_id'           => $food->id,
                'portion_size'      => $portion,
            ]);
        }
    }

    private function makePlan(ChildAssessment $assessment, Carbon $start, int $days, string $status = 'active', string $review = 'approved'): DietPlan
    {
        return DietPlan::create([
            'child_assessment_id' => $assessment->id,
            'status'              => $status,
            'review_status'       => $review,
            'plan_data'           => ['schedule' => ['start_date' => $start->toDateString(), 'days' => $days]],
        ]);
    }

    public function test_monthly_plan_keeps_each_day_own_meals_and_real_dates(): void
    {
        $parent = User::factory()->create();
        [, $assessment] = $this->makeChild($parent);

        $start = Carbon::today()->subDays(8);
        $plan  = $this->makePlan($assessment, $start, 30);

        $weekOne   = $this->makeFood('Week One Poha', 100, 'Breakfast');
        $weekThree = $this->makeFood('Week Three Idli', 100, 'Breakfast');
        $this->addMeal($plan, 0, 'Breakfast', [[$weekOne, '100g']], 1);
        $this->addMeal($plan, 14, 'Breakfast', [[$weekThree, '100g']], 2);

        $response = $this->actingAs($parent)->get(route('meal-plan'));
        $response->assertOk()->assertViewIs('pages.user-panel.meal-plan');

        $mealPlan = $response->viewData('mealPlan');
        $this->assertSame(30, $mealPlan['total_days']);
        $this->assertTrue($mealPlan['is_monthly']);

        $byIndex = collect($mealPlan['days'])->keyBy('index');
        $this->assertSame($start->copy()->addDays(14)->toDateString(), $byIndex[14]['iso']);
        $this->assertSame(3, $byIndex[14]['week']);
        $this->assertSame('Week Three Idli', $byIndex[14]['meals'][0]['items'][0]['name']);
        $this->assertSame('Week One Poha', $byIndex[0]['meals'][0]['items'][0]['name']);

        // Day 7 has the same weekday as day 0 but must not show day 0's food.
        $this->assertSame([], $byIndex[7]['meals']);

        // Today (start + 8 days) is the active day, in week 2.
        $this->assertSame(8, $mealPlan['active_day_index']);
        $this->assertTrue($byIndex[8]['is_today']);
    }

    public function test_lunch_shows_two_items_with_portion_and_scaled_kcal(): void
    {
        $parent = User::factory()->create();
        [, $assessment] = $this->makeChild($parent);
        $plan = $this->makePlan($assessment, Carbon::today(), 7);

        $main = $this->makeFood('Test Rice', 130);
        $side = $this->makeFood('Test Dal', 100);
        // serving_g = 100, so a 200g portion doubles the kcal and 150g gives 1.5x.
        $this->addMeal($plan, 0, 'Lunch', [[$main, '200g'], [$side, '150g']], 1);

        $response = $this->actingAs($parent)->get(route('meal-plan'))->assertOk();

        $lunch = $response->viewData('mealPlan')['days'][0]['meals'][0];
        $this->assertSame('Lunch', $lunch['slot']);
        $this->assertCount(2, $lunch['items']);
        $this->assertSame('200 g', $lunch['items'][0]['portion']);
        $this->assertSame(260, $lunch['items'][0]['kcal']);
        $this->assertSame('150 g', $lunch['items'][1]['portion']);
        $this->assertSame(150, $lunch['items'][1]['kcal']);
        $this->assertSame(410, $lunch['kcal']);
    }

    public function test_page_shows_the_real_child_and_no_hardcoded_demo_text(): void
    {
        $parent = User::factory()->create();
        [, $assessment] = $this->makeChild($parent, 'Mira');
        $this->makePlan($assessment, Carbon::today(), 30);

        $html = $this->actingAs($parent)->get(route('meal-plan'))->assertOk()->getContent();

        $this->assertStringContainsString('Mira', $html);
        $this->assertStringContainsString('8 years', $html);
        $this->assertStringContainsString('Vegetarian', $html);
        $this->assertStringContainsString('id="weekTabBar"', $html);
        $this->assertStringNotContainsString('Arjun', $html);
        $this->assertStringNotContainsString('Greeshma', $html);
        $this->assertStringNotContainsString('West India', $html);
    }

    public function test_basic_seven_day_plan_has_no_week_tabs(): void
    {
        $parent = User::factory()->create();
        [, $assessment] = $this->makeChild($parent);
        $this->makePlan($assessment, Carbon::today(), 7);

        $response = $this->actingAs($parent)->get(route('meal-plan'))->assertOk();

        $this->assertFalse($response->viewData('mealPlan')['is_monthly']);
        $this->assertStringNotContainsString('id="weekTabBar"', $response->getContent());
    }

    public function test_unapproved_plan_shows_being_reviewed_message_and_no_days(): void
    {
        $parent = User::factory()->create();
        [, $assessment] = $this->makeChild($parent);
        $this->makePlan($assessment, Carbon::today(), 30, 'pending_review', 'pending');

        $response = $this->actingAs($parent)->get(route('meal-plan'))->assertOk();

        $response->assertViewHas('reviewPending', true);
        $response->assertViewMissing('mealPlan');
        $response->assertSee('Your chart is being reviewed');
    }

    public function test_page_has_no_stray_text_or_broken_emoji(): void
    {
        $parent = User::factory()->create();
        [, $assessment] = $this->makeChild($parent);
        $this->makePlan($assessment, Carbon::today(), 7);

        $html = $this->actingAs($parent)->get(route('meal-plan'))->assertOk()->getContent();

        $this->assertStringNotContainsString('put this data in', $html);
        $this->assertStringNotContainsString("\u{00F0}\u{0178}", $html); // mojibake lead bytes
    }
}