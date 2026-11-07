<?php

namespace Tests\Feature;

use App\Models\DietSubscription;
use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DietPlanMeal;
use App\Models\Nutrition\IcmrNutritionTarget;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Nutrition\RuleBasedDietPlanEngine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * End-to-end test for RuleBasedDietPlanEngine using an in-memory SQLite database.
 * All fixtures are created inside the tests — no seeders or external data files.
 */
class EngineEndToEndTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Fixture helpers
    // ------------------------------------------------------------------

    /**
     * Insert one ICMR row covering age 5–7, sex = 'all'.
     * Using nutrient values that match the IcmrNutritionTarget::toNutrientArray() keys.
     */
    private function seedIcmrTarget(): void
    {
        DB::table('icmr_nutrition_targets')->insert([
            'age_min'          => 5,
            'age_max'          => 7,
            'sex'              => 'all',
            'energy_kcal'      => 1360.0,
            'protein_g'        => 16.0,
            'fat_g'            => 25.0,
            'fiber_g'          => 20.0,
            'calcium_mg'       => 550.0,
            'iron_mg'          => 11.0,
            'zinc_mg'          => 4.5,
            'magnesium_mg'     => 125.0,
            'vitamin_a_mcg'    => 510.0,
            'vitamin_c_mg'     => 35.0,
            'vitamin_d_mcg'    => 15.0,
            'thiamin_b1_mg'    => 0.9,
            'riboflavin_b2_mg' => 1.3,
            'niacin_b3_mg'     => 9.0,
            'vitamin_b6_mg'    => 1.2,
            'folate_mcg'       => 135.0,
            'vitamin_b12_mcg'  => 2.2,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    /**
     * Insert ~40 food rows covering Breakfast, Snack (Morning Snack / Evening Snack),
     * Lunch, and Dinner.  Flags are stored as strings ('Yes'/'No') per the foods schema.
     *
     * The FoodFilterPipeline::forSlot() maps any slot containing 'Snack' → 'snack',
     * so we set food.slot = 'snack' for both morning and evening snack foods.
     *
     * Each slot gets 8 foods (8 × 5 slots = 40) so the engine always has at least
     * 5 distinct choices and can pick different foods across days.
     */
    private function seedFoods(): void
    {
        $slots = [
            'breakfast' => 8,
            'snack'     => 8,   // covers Morning Snack & Evening Snack
            'lunch'     => 8,
            'dinner'    => 8,
        ];

        $id = 1;
        foreach ($slots as $slot => $count) {
            for ($i = 1; $i <= $count; $i++) {
                DB::table('foods')->insert([
                    'id'            => $id,
                    'dish_id'       => "TEST-{$slot}-{$i}",
                    'dish_name'     => ucfirst($slot)." Food {$i}",
                    'slot'          => $slot,
                    'region'        => 'Pan-India',
                    'food_group'    => 'Cereal',
                    'diet_type'     => 'vegetarian',
                    'serving_g'     => 100,
                    'kid_accept_1_5' => 4,
                    'repeat_gap_days' => 2,   // must wait 2 days before reuse
                    // All flags explicitly set to 'No' so vegetarian filter passes.
                    'is_veg'          => 'Yes',
                    'is_vegan'        => 'Yes',
                    'is_jain_ok'      => 'Yes',
                    'contains_dairy'  => 'No',
                    'contains_gluten' => 'No',
                    'contains_egg'    => 'No',
                    'contains_nuts'   => 'No',
                    'contains_soy'    => 'No',
                    'contains_sesame' => 'No',
                    'contains_honey'  => 'No',
                    // Modest nutrient values — the targets are well above zero so gaps
                    // will always be computed; exact adequacy is not what we assert here.
                    'energy_kcal'   => 80.0,
                    'protein_g'     => 3.0,
                    'fat_g'         => 2.0,
                    'fibre_total_g' => 1.5,
                    'calcium_mg'    => 40.0,
                    'iron_mg'       => 1.0,
                    'zinc_mg'       => 0.5,
                    'magnesium_mg'  => 15.0,
                    'vit_a_rae_ug'  => 30.0,
                    'vit_c_mg'      => 5.0,
                    'vit_d_ug'      => 0.5,
                    'b1_mg'         => 0.1,
                    'b2_mg'         => 0.1,
                    'b3_mg'         => 0.5,
                    'b6_mg'         => 0.1,
                    'folate_ug'     => 10.0,
                    'b12_ug'        => 0.1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
                $id++;
            }
        }
    }

    /**
     * Make a child born 6 years ago (falls in ICMR 5–7 band),
     * an assessment with 3-meals pattern, and a subscription with the
     * given plan slug (so PlanAccess::slugForChild resolves it).
     */
    private function makeChildWithSubscription(string $slug): array
    {
        $parent = User::factory()->create();
        $child  = Child::create([
            'user_id' => $parent->id,
            'name'    => 'Test Child',
            'sex'     => 'boy',
            'dob'     => now()->subYears(6)->toDateString(),
        ]);
        $assessment = ChildAssessment::create([
            'child_id'          => $child->id,
            'model_level'       => 'basic',
            'current_weight_kg' => 20.0,
            'current_height_cm' => 115.0,
            'diet_type'         => 'vegetarian',
            'meal_pattern'      => '3 meals',
        ]);
        $plan = SubscriptionPlan::create([
            'name'          => ucfirst($slug).' Plan',
            'slug'          => $slug,
            'price'         => 100,
            'monthly_price' => 100,
            'is_active'     => true,
        ]);
        $subscription = DietSubscription::create([
            'user_id'              => $parent->id,
            'child_id'             => $child->id,
            'subscription_plan_id' => $plan->id,
            'model'                => ucfirst($slug),
            'status'               => 'active',
            'payment_status'       => 'paid',
            'amount_paid'          => 100,
            'expires_at'           => now()->addYear(),
        ]);
        return [$child, $assessment, $subscription];
    }

    // ------------------------------------------------------------------
    // Tests
    // ------------------------------------------------------------------

    /**
     * Basic plan → exactly 7 DietPlanMeal rows per slot × 3 slots = 21 meals.
     */
    public function test_basic_child_engine_generates_7_days(): void
    {
        $this->seedIcmrTarget();
        $this->seedFoods();

        [, $assessment] = $this->makeChildWithSubscription('basic');

        $engine = app(RuleBasedDietPlanEngine::class);
        $result = $engine->generate($assessment, ['_seed' => 42]);

        $this->assertTrue($result['success']);
        $this->assertSame(7, $result['nutrient_gaps'] ? 7 : 0);   // indirect; check plan directly

        // plan_data schedule
        $planId = $result['diet_plan_id'];
        $plan   = \App\Models\Nutrition\DietPlan::find($planId);
        $this->assertSame(7, $plan->plan_data['schedule']['days']);
        $this->assertSame(7, $result['diet_plan_id'] ? 7 : 0);    // keep above; real assertion:

        // 7 days × 3 slots = 21 meals
        $this->assertSame(21, DietPlanMeal::where('diet_plan_id', $planId)->count());
    }

    /**
     * Plus plan in a 30-day month → exactly 30 days.
     */
    public function test_plus_child_engine_generates_30_days_in_30_day_month(): void
    {
        $this->seedIcmrTarget();
        $this->seedFoods();

        // April 2027 has 30 days.
        Carbon::setTestNow('2027-04-01 00:00:00');

        [, $assessment] = $this->makeChildWithSubscription('plus');

        $engine = app(RuleBasedDietPlanEngine::class);
        $result = $engine->generate($assessment, ['_seed' => 99]);

        Carbon::setTestNow();

        $this->assertTrue($result['success']);
        $plan = \App\Models\Nutrition\DietPlan::find($result['diet_plan_id']);
        $this->assertSame(30, $plan->plan_data['schedule']['days']);
        $this->assertSame(30 * 3, DietPlanMeal::where('diet_plan_id', $plan->id)->count());
    }

    /**
     * Plus plan in a 31-day month → exactly 31 days.
     */
    public function test_plus_child_engine_generates_31_days_in_31_day_month(): void
    {
        $this->seedIcmrTarget();
        $this->seedFoods();

        // January 2027 has 31 days.
        Carbon::setTestNow('2027-01-01 00:00:00');

        [, $assessment] = $this->makeChildWithSubscription('plus');

        $engine = app(RuleBasedDietPlanEngine::class);
        $result = $engine->generate($assessment, ['_seed' => 77]);

        Carbon::setTestNow();

        $this->assertTrue($result['success']);
        $plan = \App\Models\Nutrition\DietPlan::find($result['diet_plan_id']);
        $this->assertSame(31, $plan->plan_data['schedule']['days']);
        $this->assertSame(31 * 3, DietPlanMeal::where('diet_plan_id', $plan->id)->count());
    }

    /**
     * No dish repeats inside its repeat_gap_days window when alternatives exist.
     *
     * Each food has repeat_gap_days = 2.  We seed 8 breakfast foods so there are
     * always 7 alternatives.  With a 7-day plan the engine must not reuse any
     * breakfast food within 2 days of its previous appearance.
     */
    public function test_no_dish_repeats_inside_repeat_gap_window(): void
    {
        $this->seedIcmrTarget();
        $this->seedFoods();

        [, $assessment] = $this->makeChildWithSubscription('basic');

        $engine = app(RuleBasedDietPlanEngine::class);
        $result = $engine->generate($assessment, ['_seed' => 11]);

        $this->assertTrue($result['success']);

        $plan  = \App\Models\Nutrition\DietPlan::find($result['diet_plan_id']);
        $debug = $plan->plan_data['generation_debug']['meals'] ?? [];

        // Group selections by slot.
        $breakfastByDay = [];
        foreach ($debug as $entry) {
            if ($entry['slot'] === 'Breakfast') {
                $breakfastByDay[$entry['day_index']] = $entry['selected_food_id'];
            }
        }

        // Check no food appears within 2 days of its previous use.
        $lastUsed = [];
        foreach ($breakfastByDay as $dayIndex => $foodId) {
            if (isset($lastUsed[$foodId])) {
                $gap = $dayIndex - $lastUsed[$foodId];
                $this->assertGreaterThanOrEqual(
                    2,
                    $gap,
                    "Food {$foodId} reused only {$gap} day(s) after last use at day {$lastUsed[$foodId]}"
                );
            }
            $lastUsed[$foodId] = $dayIndex;
        }
    }

    /**
     * Week 1 (days 0–6) and week 2 (days 7–13) must not have identical breakfast menus.
     * We use a 14-day plan (a plus plan in a 14-day... we'll use carbon to set a 14-day
     * month is not possible, so we use a basic plan and override via _seed, then check
     * the debug log for variety.
     *
     * Approach: run a basic 7-day plan twice with different seeds; together the two
     * plans represent what would be weeks 1 and 2.  With 8 breakfast foods and the
     * variety penalties (-8 recent, -2 older) the engine probabilistically picks
     * different foods.  We instead run a plus plan for a 14-day span by mocking
     * planDays using a February (28 days) and verifying days 0–6 vs 7–13 differ.
     *
     * Since we cannot mock Carbon month-length to exactly 14, we run the 28-day plan
     * and assert that the intersection of week-1 and week-2 breakfast sets is not
     * identical (i.e. at least one breakfast food in week 2 was not used every single
     * day in week 1 in the same position).
     */
    public function test_week_2_breakfast_differs_from_week_1(): void
    {
        $this->seedIcmrTarget();
        $this->seedFoods();

        // February 2027 = 28 days.
        Carbon::setTestNow('2027-02-01 00:00:00');

        [, $assessment] = $this->makeChildWithSubscription('plus');

        $engine = app(RuleBasedDietPlanEngine::class);
        $result = $engine->generate($assessment, ['_seed' => 7]);

        Carbon::setTestNow();

        $this->assertTrue($result['success']);

        $plan  = \App\Models\Nutrition\DietPlan::find($result['diet_plan_id']);
        $debug = $plan->plan_data['generation_debug']['meals'] ?? [];

        $week1 = [];
        $week2 = [];
        foreach ($debug as $entry) {
            if ($entry['slot'] !== 'Breakfast') {
                continue;
            }
            $d = $entry['day_index'];
            if ($d < 7) {
                $week1[] = $entry['selected_food_id'];
            } elseif ($d < 14) {
                $week2[] = $entry['selected_food_id'];
            }
        }

        // The two 7-element sequences must not be identical.
        $this->assertNotSame(
            $week1,
            $week2,
            'Week 1 and week 2 breakfast sequences are identical — variety scoring is not working.'
        );
    }

    /**
     * nutrient_gaps in the returned result has all four required keys per nutrient.
     */
    public function test_engine_nutrient_gaps_have_required_keys(): void
    {
        $this->seedIcmrTarget();
        $this->seedFoods();

        [, $assessment] = $this->makeChildWithSubscription('basic');

        $engine = app(RuleBasedDietPlanEngine::class);
        $result = $engine->generate($assessment, ['_seed' => 1]);

        $this->assertTrue($result['success']);

        $gaps = $result['nutrient_gaps'];
        $this->assertNotEmpty($gaps, 'nutrient_gaps must not be empty.');

        foreach ($gaps as $nutrient => $gap) {
            $this->assertArrayHasKey('target',           $gap, "Missing 'target' for {$nutrient}");
            $this->assertArrayHasKey('absorbed_amount',  $gap, "Missing 'absorbed_amount' for {$nutrient}");
            $this->assertArrayHasKey('adequacy_percent', $gap, "Missing 'adequacy_percent' for {$nutrient}");
            $this->assertArrayHasKey('gap_category',     $gap, "Missing 'gap_category' for {$nutrient}");
            $this->assertContains(
                $gap['gap_category'],
                ['Met', 'Partial gap', 'Significant gap', 'Critical gap'],
                "Unexpected gap_category value for {$nutrient}"
            );
        }
    }

    /**
     * day_index is stored on every DietPlanMeal row created by the engine.
     */
    public function test_engine_stores_day_index_on_every_meal(): void
    {
        $this->seedIcmrTarget();
        $this->seedFoods();

        [, $assessment] = $this->makeChildWithSubscription('basic');

        $engine = app(RuleBasedDietPlanEngine::class);
        $result = $engine->generate($assessment, ['_seed' => 5]);

        $this->assertTrue($result['success']);

        $planId = $result['diet_plan_id'];

        // Every meal row must have a non-null day_index.
        $nullCount = DietPlanMeal::where('diet_plan_id', $planId)
            ->whereNull('day_index')
            ->count();
        $this->assertSame(0, $nullCount, 'Some DietPlanMeal rows have day_index = NULL after engine generation.');

        // day_index values must span 0 to 6 (7-day plan, 3 slots each).
        $indices = DietPlanMeal::where('diet_plan_id', $planId)
            ->pluck('day_index')
            ->unique()
            ->sort()
            ->values()
            ->toArray();
        $this->assertSame([0, 1, 2, 3, 4, 5, 6], $indices);
    }
}
