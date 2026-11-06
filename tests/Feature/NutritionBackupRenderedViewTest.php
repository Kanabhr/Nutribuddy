<?php

namespace Tests\Feature;

use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DietPlan;
use App\Models\Nutrition\DietPlanItem;
use App\Models\Nutrition\DietPlanMeal;
use App\Models\Nutrition\FoodMaster;
use App\Models\Nutrition\IcmrNutritionTarget;
use App\Models\Nutrition\MealItemLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NutritionBackupRenderedViewTest
 *
 * Hits GET /user/pages/nutrition-backup and asserts:
 *  1. Week tabs (Week 1 … Week 5) are present for a 30-day plan.
 *  2. Date chips for non-active weeks carry nutri-date-hidden; active week
 *     has at least one visible chip.
 *  3. The carbs row shows a plain planned value with no "/" and no stale
 *     hard-coded target (e.g. "/ 236" must never appear).
 *  4. "Not provided" appears when assessment has no height/weight.
 *  5. Food items start unticked (no meal-taken class) when the DB has no
 *     MealItemLog rows for today.
 */
class NutritionBackupRenderedViewTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * Build an approved 30-day diet plan for a child with empty meal slots
     * (no food items — avoids needing FoodMaster seed rows).
     * The plan structure is sufficient for week-tab, date-chip, and
     * carb-target assertions.
     */
    private function createApproved30DayPlanNoItems(Child $child, ChildAssessment $assessment): DietPlan
    {
        // Start the plan 3 days ago so today is day 3 (within week 1).
        $startDate = now()->subDays(3)->format('Y-m-d');

        $plan = DietPlan::create([
            'child_assessment_id' => $assessment->id,
            'status'              => 'active',
            'review_status'       => 'approved',
            'plan_data'           => [
                'schedule' => ['start_date' => $startDate, 'days' => 30],
            ],
        ]);

        // Create 30 × 3 DietPlanMeal rows — no items attached.
        $order = 0;
        for ($dayIndex = 0; $dayIndex < 30; $dayIndex++) {
            foreach (['Breakfast', 'Lunch', 'Dinner'] as $slot) {
                DietPlanMeal::create([
                    'diet_plan_id' => $plan->id,
                    'meal_slot'    => $slot,
                    'sort_order'   => $order++,
                    'day_index'    => $dayIndex,
                ]);
            }
        }

        return $plan;
    }

    // ---------------------------------------------------------------
    // 1. Week tabs are rendered for a multi-week plan
    // ---------------------------------------------------------------

    public function test_week_tabs_are_rendered_for_a_30_day_plan(): void
    {
        $parent = User::factory()->create();
        $child  = Child::create(['user_id' => $parent->id, 'name' => 'Tab Kid', 'sex' => 'boy']);
        $assessment = ChildAssessment::create([
            'child_id'          => $child->id,
            'model_level'       => 'basic',
            'current_weight_kg' => 20,
            'current_height_cm' => 115,
            'diet_type'         => 'vegetarian',
            'meal_pattern'      => '3 meals',
        ]);
        $this->createApproved30DayPlanNoItems($child, $assessment);

        $response = $this->actingAs($parent)->get(route('nutrition.backup'));

        $response->assertOk();

        // Five week tabs must appear (30 days ÷ 7 = 5 weeks).
        foreach (range(1, 5) as $wn) {
            $response->assertSee('Week ' . $wn);
        }

        // The tab elements carry the nutri-week-tab class.
        $response->assertSee('nutri-week-tab', false);
    }

    // ---------------------------------------------------------------
    // 2. Date chips for non-active weeks carry hidden class
    // ---------------------------------------------------------------

    public function test_date_chips_for_non_active_weeks_carry_hidden_class(): void
    {
        $parent = User::factory()->create();
        $child  = Child::create(['user_id' => $parent->id, 'name' => 'Chip Kid', 'sex' => 'girl']);
        $assessment = ChildAssessment::create([
            'child_id'          => $child->id,
            'model_level'       => 'basic',
            'current_weight_kg' => 18,
            'current_height_cm' => 108,
            'diet_type'         => 'vegetarian',
            'meal_pattern'      => '3 meals',
        ]);
        $this->createApproved30DayPlanNoItems($child, $assessment);

        $html = $this->actingAs($parent)
            ->get(route('nutrition.backup'))
            ->assertOk()
            ->getContent();

        // Chips for weeks other than the active one must carry the hidden class.
        $this->assertStringContainsString(
            'nutri-date-hidden',
            $html,
            'Expected nutri-date-hidden on chips for non-active weeks.',
        );

        // Active-week chips must NOT be hidden: assert at least one chip element
        // exists without the nutri-date-hidden class.
        $activeCount = preg_match_all(
            '/nutri-date-item(?:[^"]*?)(?<!\bnutri-date-hidden\b)[^"]*?"/',
            $html
        );
        $this->assertGreaterThan(0, $activeCount,
            'At least one date chip should be visible (not carrying nutri-date-hidden).');
    }

    // ---------------------------------------------------------------
    // 3. Carbs row: plain value only — no target, no "/ 236"
    // ---------------------------------------------------------------

    public function test_carbs_row_shows_no_target_and_no_stale_236_value(): void
    {
        $parent = User::factory()->create();
        $child  = Child::create(['user_id' => $parent->id, 'name' => 'Carb Kid', 'sex' => 'boy']);
        $assessment = ChildAssessment::create([
            'child_id'          => $child->id,
            'model_level'       => 'basic',
            'current_weight_kg' => 22,
            'current_height_cm' => 120,
            'diet_type'         => 'vegetarian',
            'meal_pattern'      => '3 meals',
        ]);
        $this->createApproved30DayPlanNoItems($child, $assessment);

        $html = $this->actingAs($parent)
            ->get(route('nutrition.backup'))
            ->assertOk()
            ->getContent();

        // The blade renders the carbs label as "Carbs planned".
        $this->assertStringContainsString('Carbs planned', $html);

        // The stale hard-coded carb target must never appear in the output.
        $this->assertStringNotContainsString('/ 236', $html,
            'Stale carb target 236 must not appear in the rendered HTML.');

        // The carbsVal span must not show a slash (no "X / Yg" pattern).
        if (preg_match('/<span[^>]*id="carbsVal"[^>]*>(.*?)<\/span>/s', $html, $m)) {
            $this->assertStringNotContainsString('/', $m[1],
                'carbsVal must show a plain value, not a "consumed / target" pattern. Got: ' . $m[1]);
        }
    }

    // ---------------------------------------------------------------
    // 4. "Not provided" appears when height/weight are absent
    // ---------------------------------------------------------------

    public function test_not_provided_shown_when_child_has_no_measurements(): void
    {
        $parent = User::factory()->create();
        $child  = Child::create(['user_id' => $parent->id, 'name' => 'Unmeasured', 'sex' => 'girl']);
        // Assessment with no height/weight — both default to null.
        ChildAssessment::create([
            'child_id'    => $child->id,
            'model_level' => 'basic',
            'diet_type'   => 'vegetarian',
        ]);

        $response = $this->actingAs($parent)->get(route('nutrition.backup'));

        $response->assertOk();
        $response->assertSee('Not provided');
    }

    // ---------------------------------------------------------------
    // 5. Items start unticked when DB has no consumed logs for today
    // ---------------------------------------------------------------

    public function test_food_items_start_unticked_when_no_logs_exist(): void
    {
        $parent = User::factory()->create();
        $child  = Child::create(['user_id' => $parent->id, 'name' => 'Fresh Kid', 'sex' => 'boy']);
        $assessment = ChildAssessment::create([
            'child_id'          => $child->id,
            'model_level'       => 'basic',
            'current_weight_kg' => 19,
            'current_height_cm' => 112,
            'diet_type'         => 'vegetarian',
            'meal_pattern'      => '3 meals',
        ]);
        $this->createApproved30DayPlanNoItems($child, $assessment);

        // No MealItemLog rows — nothing should be pre-ticked.
        $this->assertSame(0, MealItemLog::count());

        $html = $this->actingAs($parent)
            ->get(route('nutrition.backup'))
            ->assertOk()
            ->getContent();

        // The meal-taken class (which causes strike-through) must not appear.
        $this->assertStringNotContainsString('meal-taken', $html,
            'No food item should be pre-ticked when there are no MealItemLog rows for today.');
    }

    // ---------------------------------------------------------------
    // 6. 14-day plan: day index 7 shows week 2 food, not week 1
    // ---------------------------------------------------------------

    /**
     * Helper: create a minimal food row in the `foods` table.
     */
    private function createFood(string $dishName, float $kcal = 300, float $protein = 10, float $carb = 40, float $fat = 8): FoodMaster
    {
        // FoodMaster has no $fillable (read-only catalogue model); unguard
        // temporarily so we can seed test rows.
        \Illuminate\Database\Eloquent\Model::unguard();

        $food = FoodMaster::create([
            'dish_id'    => 'TEST-' . uniqid(),
            'dish_name'  => $dishName,
            'slot'       => 'Breakfast',
            'serving_g'  => 150,
            'energy_kcal'  => $kcal,
            'protein_g'    => $protein,
            'carb_g'       => $carb,
            'fat_g'        => $fat,
            'is_veg'       => 1,
            'is_vegan'     => 0,
            'is_jain_ok'   => 1,
            'contains_dairy' => 0,
            'contains_gluten' => 0,
            'contains_egg' => 0,
            'contains_nuts' => 0,
        ]);

        \Illuminate\Database\Eloquent\Model::reguard();

        return $food;
    }

    /**
     * Helper: build an approved multi-day plan where each day gets a
     * distinct food in its Breakfast slot.
     *
     * @param array<int, array{food: FoodMaster, name: string}> $dayFoods
     */
    private function createApprovedMultiDayPlanWithFoods(
        Child $child,
        ChildAssessment $assessment,
        int $totalDays,
        array $dayFoods,
    ): DietPlan {
        $startDate = now()->subDays(2)->format('Y-m-d');

        $plan = DietPlan::create([
            'child_assessment_id' => $assessment->id,
            'status'              => 'active',
            'review_status'       => 'approved',
            'plan_data'           => [
                'schedule' => ['start_date' => $startDate, 'days' => $totalDays],
            ],
        ]);

        $order = 0;
        for ($dayIndex = 0; $dayIndex < $totalDays; $dayIndex++) {
            $meal = DietPlanMeal::create([
                'diet_plan_id' => $plan->id,
                'meal_slot'    => 'Breakfast',
                'sort_order'   => $order++,
                'day_index'    => $dayIndex,
                'focus'       => 'Test focus day ' . $dayIndex,
            ]);

            $food = $dayFoods[$dayIndex]['food'] ?? $dayFoods[0]['food'];
            DietPlanItem::create([
                'diet_plan_meal_id' => $meal->id,
                'food_id'           => $food->id,
                'portion_size'      => 150,
                'is_alternative'    => false,
            ]);
        }

        return $plan;
    }

    /**
     * 14-day plan with different foods in week 2.
     * Asserts that day index 7's panel shows week 2's food and not week 1's,
     * that chips are grouped per week, and nothing is ticked without logs.
     */
    public function test_14_day_plan_day_7_shows_week_2_food_not_week_1(): void
    {
        $parent = User::factory()->create();
        $child  = Child::create([
            'user_id' => $parent->id,
            'name'    => 'Multi-Week Kid',
            'sex'     => 'boy',
            'dob'     => now()->subYears(5)->toDateString(),
        ]);
        $assessment = ChildAssessment::create([
            'child_id'          => $child->id,
            'model_level'       => 'basic',
            'current_weight_kg' => 18,
            'current_height_cm' => 110,
            'diet_type'         => 'vegetarian',
            'meal_pattern'      => '3 meals',
        ]);

        // Create 14 foods: week 1 uses "W1 Food N", week 2 uses "W2 Food N".
        $dayFoods = [];
        for ($d = 0; $d < 14; $d++) {
            $weekPrefix = ($d < 7) ? 'W1' : 'W2';
            $food = $this->createFood("{$weekPrefix} Food Day {$d}", 250 + $d, 8 + $d);
            $dayFoods[$d] = ['food' => $food, 'name' => "{$weekPrefix} Food Day {$d}"];
        }

        $this->createApprovedMultiDayPlanWithFoods($child, $assessment, 14, $dayFoods);

        // No logs — nothing should be ticked.
        $this->assertSame(0, MealItemLog::count());

        $html = $this->actingAs($parent)
            ->get(route('nutrition.backup'))
            ->assertOk()
            ->getContent();

        // Day index 7 panel must contain week 2's food name, not week 1's.
        // Blade comments are stripped from output, so we extract panel content
        // between the current panel's opening tag and the next panel's opening
        // tag (or the end of the food-view section).
        $day7Html = '';
        $day0Html = '';
        if (preg_match('/data-food-day="7"[^>]*>(.*?)(?:data-food-day="8"|$)/s', $html, $m7)) {
            $day7Html = $m7[1];
        }
        if (preg_match('/data-food-day="0"[^>]*>(.*?)(?:data-food-day="1"|$)/s', $html, $m0)) {
            $day0Html = $m0[1];
        }

        $this->assertNotEmpty($day7Html, 'Day index 7 panel should exist in the rendered HTML.');
        $this->assertNotEmpty($day0Html, 'Day index 0 panel should exist in the rendered HTML.');

        // Day 7 panel must contain week 2's food, not week 1's.
        $this->assertStringContainsString($dayFoods[7]['name'], $day7Html,
            'Day index 7 panel should contain week 2 food "' . $dayFoods[7]['name'] . '".');
        $this->assertStringNotContainsString($dayFoods[0]['name'], $day7Html,
            'Day index 7 panel must not show week 1 food "' . $dayFoods[0]['name'] . '".');

        // Day 0 panel must contain week 1's food, not week 2's.
        $this->assertStringContainsString($dayFoods[0]['name'], $day0Html,
            'Day index 0 panel should contain week 1 food "' . $dayFoods[0]['name'] . '".');
        $this->assertStringNotContainsString($dayFoods[7]['name'], $day0Html,
            'Day index 0 panel must not show week 2 food "' . $dayFoods[7]['name'] . '".');

        // Week tabs: Week 1 and Week 2 must be present.
        $this->assertStringContainsString('Week 1', $html);
        $this->assertStringContainsString('Week 2', $html);

        // Chips: day index 7 chip must carry data-week="2".
        $this->assertMatchesRegularExpression(
            '/data-day-index="7"[^>]*data-week="2"/',
            $html,
            'Day index 7 chip should carry data-week="2".',
        );
        $this->assertMatchesRegularExpression(
            '/data-day-index="0"[^>]*data-week="1"/',
            $html,
            'Day index 0 chip should carry data-week="1".',
        );

        // Nothing should be ticked: no meal-taken class anywhere.
        $this->assertStringNotContainsString('meal-taken', $html,
            'No food items should be pre-ticked when no MealItemLog rows exist.');
    }

    // ---------------------------------------------------------------
    // 7. Zero target treated as "not available"
    // ---------------------------------------------------------------

    /**
     * A target of 0 (e.g. fat_g = 0.0 in a sparse ICMR row) must be treated as
     * "not available" — never displayed as a "0" target or used in a
     * percentage. The page should show "Target not available" instead.
     */
    public function test_zero_fat_target_shows_not_available_instead_of_zero(): void
    {
        $parent = User::factory()->create();
        $child  = Child::create([
            'user_id' => $parent->id,
            'name'    => 'Zero Fat Kid',
            'sex'     => 'girl',
            'dob'     => now()->subYears(5)->toDateString(),
        ]);
        ChildAssessment::create([
            'child_id'          => $child->id,
            'model_level'       => 'basic',
            'current_weight_kg' => 17,
            'current_height_cm' => 105,
            'diet_type'         => 'vegetarian',
            'meal_pattern'      => '3 meals',
        ]);

        // ICMR row with fat_g = 0 — the "not available" scenario.
        // updateOrCreate overwrites the migration-seeded 4-6/all row (which
        // also has fat_g = 0) so there's no duplicate-row ambiguity.
        IcmrNutritionTarget::updateOrCreate(
            ['age_min' => 4, 'age_max' => 6, 'sex' => 'all'],
            [
                'energy_kcal'  => 1360.0,
                'protein_g'    => 16.0,
                'fat_g'        => 0.0,    // ← zero fat target
                'fiber_g'      => 20.0,
                'calcium_mg'   => 550.0,
                'iron_mg'      => 11.0,
                'zinc_mg'      => 4.5,
                'magnesium_mg' => 125.0,
            ]
        );

        $html = $this->actingAs($parent)
            ->get(route('nutrition.backup'))
            ->assertOk()
            ->getContent();

        // Per-nutrient: the energy (1360) and protein (16) targets are
        // available, so the calorie ring — which needs the energy target — and
        // the protein bar must still render.
        $this->assertStringContainsString('cal-ring-container', $html,
            'The calorie ring must render when the energy target is available.');
        $this->assertStringContainsString('of 1360', $html,
            'The available energy target (1360 kcal) must be displayed.');
        $this->assertStringContainsString('/ 16g', $html,
            'The available protein target (16g) must still render its bar.');

        // The fat target is 0 → "not available" for that nutrient only: a
        // "not available" note is shown instead of a numeric bar.
        $this->assertStringContainsString('not available', $html,
            'A fat_g target of 0 must show a "not available" note for that nutrient.');

        // A zero target must never be displayed as a numeric target.
        $this->assertStringNotContainsString('/ 0g', $html,
            'A zero target must never be displayed as "/ 0g".');
        $this->assertStringNotContainsString('of 0', $html,
            'A zero calorie target must never be displayed as "of 0".');
    }
}
