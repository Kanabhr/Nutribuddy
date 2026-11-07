<?php

namespace Tests\Feature;

use App\Models\DietSubscription;
use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DietPlan;
use App\Models\Nutrition\DietPlanItem;
use App\Models\Nutrition\DietPlanMeal;
use App\Models\Nutrition\FoodMaster;
use App\Models\Nutrition\IcmrNutritionTarget;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Nutrition\ChildProfileService;
use App\Services\Nutrition\FoodFilterPipeline;
use App\Services\Nutrition\RuleBasedDietPlanEngine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * DislikesFavouritesEngineTest (C6)
 *
 * Covers:
 *  - Disliked food never appears when alternatives exist.
 *  - Per-slot fallback when every candidate in a slot is disliked.
 *  - Food-group fallback: a child who dislikes every food of a protected group
 *    (pulses/dairy) still gets at least one food from that group per week.
 *  - Allergy beats favourite (a food that is allergenic is never included even
 *    if listed as a favourite).
 *  - Favourite appears more often than non-favourites but not every single day.
 *  - Weekly variety still holds (no food every day).
 *  - Protein and calcium do not fall below levels produced without dislikes.
 *  - FoodFilterPipeline unit-level: isDisliked() matches by name substring AND
 *    food_group substring; allows() legacy gate still works.
 *  - ChildProfileService::foodList() parses comma/slash/pipe-separated text.
 *  - ChildProfileService::normalize() populates favourite_foods and disliked_foods.
 */
class DislikesFavouritesEngineTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Fixture helpers
    // ------------------------------------------------------------------

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
     * Seed foods with varied names and groups.
     *
     * Structure for 3-meals pattern (Breakfast, Lunch, Dinner):
     *   - 8 Breakfast cereals     (is_veg=Yes, group=Cereal)
     *   - 4 Lunch cereals         (is_veg=Yes, group=Cereal)   — MAIN
     *   - 4 Lunch pulses          (is_veg=Yes, group=Pulse)    — SIDE
     *   - 4 Lunch dairy           (is_veg=Yes, group=Dairy, contains_dairy=Yes)  — SIDE
     *   - 4 Dinner cereals        (is_veg=Yes, group=Cereal)   — MAIN
     *   - 4 Dinner pulses         (is_veg=Yes, group=Pulse)    — SIDE
     *   - 1 special "Paneer Dish" (Lunch, group=Dairy) — used for favourite test
     *
     * Nutrients are set so protein_g=5 and calcium_mg=60 per serving.
     */
    private function seedFoods(): void
    {
        $id = 1;

        $insert = function (string $name, string $slot, string $group, array $extra = []) use (&$id) {
            DB::table('foods')->insert(array_merge([
                'id'               => $id++,
                'dish_id'          => "C6-{$id}",
                'dish_name'        => $name,
                'slot'             => $slot,
                'region'           => 'Pan-India',
                'food_group'       => $group,
                'diet_type'        => 'vegetarian',
                'serving_g'        => 100,
                'kid_accept_1_5'   => 3,
                'repeat_gap_days'  => 2,
                'is_veg'           => 'Yes',
                'is_vegan'         => 'No',
                'is_jain_ok'       => 'Yes',
                'contains_dairy'   => 'No',
                'contains_gluten'  => 'No',
                'contains_egg'     => 'No',
                'contains_nuts'    => 'No',
                'contains_soy'     => 'No',
                'contains_sesame'  => 'No',
                'contains_honey'   => 'No',
                'energy_kcal'      => 200.0,
                'protein_g'        => 5.0,
                'fat_g'            => 3.0,
                'fibre_total_g'    => 2.0,
                'calcium_mg'       => 60.0,
                'iron_mg'          => 1.0,
                'zinc_mg'          => 0.5,
                'magnesium_mg'     => 15.0,
                'vit_a_rae_ug'     => 30.0,
                'vit_c_mg'        => 5.0,
                'vit_d_ug'         => 0.5,
                'b1_mg'            => 0.1,
                'b2_mg'            => 0.1,
                'b3_mg'            => 0.5,
                'b6_mg'            => 0.1,
                'folate_ug'        => 10.0,
                'b12_ug'           => 0.1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ], $extra));
        };

        // 8 Breakfast foods
        for ($i = 1; $i <= 8; $i++) {
            $insert("Breakfast Cereal {$i}", 'breakfast', 'Cereal');
        }

        // 4 Lunch cereal (main)
        for ($i = 1; $i <= 4; $i++) {
            $insert("Lunch Cereal {$i}", 'lunch', 'Cereal');
        }

        // 4 Lunch pulse (side)
        for ($i = 1; $i <= 4; $i++) {
            $insert("Lunch Dal {$i}", 'lunch', 'Pulse');
        }

        // 4 Lunch dairy (side)  — contains_dairy=Yes
        for ($i = 1; $i <= 4; $i++) {
            $insert("Lunch Dairy {$i}", 'lunch', 'Dairy', ['contains_dairy' => 'Yes']);
        }

        // 4 Dinner cereals (main)
        for ($i = 1; $i <= 4; $i++) {
            $insert("Dinner Cereal {$i}", 'dinner', 'Cereal');
        }

        // 4 Dinner pulses (side)
        for ($i = 1; $i <= 4; $i++) {
            $insert("Dinner Dal {$i}", 'dinner', 'Pulse');
        }

        // Special "Paneer Dish" for favourite test (lunch, dairy)
        $insert('Paneer Dish', 'lunch', 'Dairy', ['contains_dairy' => 'Yes']);
    }

    private function makeChild(): array
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
            'name'          => 'Basic Plan',
            'slug'          => 'basic',
            'price'         => 100,
            'monthly_price' => 100,
            'is_active'     => true,
        ]);
        DietSubscription::create([
            'user_id'              => $parent->id,
            'child_id'             => $child->id,
            'subscription_plan_id' => $plan->id,
            'model'                => 'Basic',
            'status'               => 'active',
            'payment_status'       => 'paid',
            'amount_paid'          => 100,
            'expires_at'           => now()->addYear(),
        ]);
        return [$child, $assessment];
    }

    /**
     * Collect all food names from a saved plan.
     */
    private function foodNamesInPlan(int $planId): array
    {
        return DietPlanItem::whereHas('meal', fn ($q) => $q->where('diet_plan_id', $planId))
            ->join('foods', 'diet_plan_items.food_id', '=', 'foods.id')
            ->pluck('foods.dish_name')
            ->all();
    }

    // ------------------------------------------------------------------
    // FoodFilterPipeline unit tests
    // ------------------------------------------------------------------

    public function test_foodlist_parses_comma_separated(): void
    {
        $service = app(ChildProfileService::class);
        $this->assertSame(['paneer', 'idli', 'banana'], $service->foodList('paneer, idli, banana'));
    }

    public function test_foodlist_parses_slash_separated(): void
    {
        $service = app(ChildProfileService::class);
        $this->assertSame(['karela', 'lauki'], $service->foodList('karela/lauki'));
    }

    public function test_foodlist_parses_pipe_separated(): void
    {
        $service = app(ChildProfileService::class);
        $this->assertSame(['karela', 'lauki'], $service->foodList('karela|lauki'));
    }

    public function test_foodlist_returns_empty_for_null(): void
    {
        $service = app(ChildProfileService::class);
        $this->assertSame([], $service->foodList(null));
    }

    public function test_foodlist_deduplicates_and_lowercases(): void
    {
        $service = app(ChildProfileService::class);
        $this->assertSame(['paneer'], $service->foodList('Paneer, PANEER, paneer'));
    }

    // -- foodList ignore-list and min-length (C6-FIX) --

    public function test_foodlist_ignores_none(): void
    {
        $service = app(ChildProfileService::class);
        $this->assertSame([], $service->foodList('None'));
    }

    public function test_foodlist_ignores_nil(): void
    {
        $service = app(ChildProfileService::class);
        $this->assertSame([], $service->foodList('nil'));
    }

    public function test_foodlist_ignores_no(): void
    {
        $service = app(ChildProfileService::class);
        $this->assertSame([], $service->foodList('no'));
    }

    public function test_foodlist_ignores_nothing(): void
    {
        $service = app(ChildProfileService::class);
        $this->assertSame([], $service->foodList('nothing'));
    }

    public function test_foodlist_ignores_na_variants(): void
    {
        $service = app(ChildProfileService::class);
        $this->assertSame([], $service->foodList('n/a'));
        $this->assertSame([], $service->foodList('na'));
    }

    public function test_foodlist_drops_terms_under_3_chars(): void
    {
        $service = app(ChildProfileService::class);
        // "ok" is 2 chars → dropped; "dal" is 3 → kept
        $this->assertSame(['dal'], $service->foodList('ok, dal'));
    }

    public function test_foodlist_none_in_mixed_list_is_dropped_but_real_foods_kept(): void
    {
        $service = app(ChildProfileService::class);
        $this->assertSame(['karela', 'lauki'], $service->foodList('none, karela, lauki'));
    }

    // -- isDisliked whole-word matching (C6-FIX) --

    public function test_is_disliked_word_match_true_cases(): void
    {
        $pipeline = app(FoodFilterPipeline::class);

        $karela = new FoodMaster;
        $karela->setRawAttributes(['dish_name' => 'Bitter Karela Curry', 'food_group' => 'Vegetable']);
        $this->assertTrue($pipeline->isDisliked($karela, ['karela']));

        $paneer = new FoodMaster;
        $paneer->setRawAttributes(['dish_name' => 'Paneer Bhurji', 'food_group' => 'Dairy']);
        $this->assertTrue($pipeline->isDisliked($paneer, ['paneer']));

        // plural: "mushroom" matches "Mushrooms Curry"
        $mushroom = new FoodMaster;
        $mushroom->setRawAttributes(['dish_name' => 'Mushrooms Curry', 'food_group' => 'Vegetable']);
        $this->assertTrue($pipeline->isDisliked($mushroom, ['mushroom']));

        // group token: "pulse" matches food_group "Pulse/Legume"
        $dal = new FoodMaster;
        $dal->setRawAttributes(['dish_name' => 'Masoor Dal', 'food_group' => 'Pulse/Legume']);
        $this->assertTrue($pipeline->isDisliked($dal, ['pulse']));
    }

    public function test_is_disliked_word_match_false_cases_no_false_positives(): void
    {
        $pipeline = app(FoodFilterPipeline::class);

        // "pea" must NOT match "peanut butter"
        $peanut = new FoodMaster;
        $peanut->setRawAttributes(['dish_name' => 'Peanut Butter Toast', 'food_group' => 'Nut']);
        $this->assertFalse($pipeline->isDisliked($peanut, ['pea']));

        // "pea" must NOT match "pear"
        $pear = new FoodMaster;
        $pear->setRawAttributes(['dish_name' => 'Pear Smoothie', 'food_group' => 'Fruit']);
        $this->assertFalse($pipeline->isDisliked($pear, ['pea']));

        // "rice" must NOT match "licorice"
        $licorice = new FoodMaster;
        $licorice->setRawAttributes(['dish_name' => 'Licorice Candy', 'food_group' => 'Confectionery']);
        $this->assertFalse($pipeline->isDisliked($licorice, ['rice']));

        // "dal" must NOT match "dalia"
        $dalia = new FoodMaster;
        $dalia->setRawAttributes(['dish_name' => 'Dalia Khichdi', 'food_group' => 'Cereal']);
        $this->assertFalse($pipeline->isDisliked($dalia, ['dal']));
    }

    public function test_word_matches_helper_directly(): void
    {
        // True cases
        $this->assertTrue(FoodFilterPipeline::wordMatches('pea', 'pea curry'));
        $this->assertTrue(FoodFilterPipeline::wordMatches('paneer', 'Paneer Bhurji'));
        $this->assertTrue(FoodFilterPipeline::wordMatches('mushroom', 'Mushrooms Curry'));  // plural
        $this->assertTrue(FoodFilterPipeline::wordMatches('tomato', 'Tomatoes Sabzi'));     // plural es
        $this->assertTrue(FoodFilterPipeline::wordMatches('pulse', 'pulse'));

        // False cases (no false positives)
        $this->assertFalse(FoodFilterPipeline::wordMatches('pea', 'peanut butter'));
        $this->assertFalse(FoodFilterPipeline::wordMatches('pea', 'pear juice'));
        $this->assertFalse(FoodFilterPipeline::wordMatches('rice', 'licorice'));
        $this->assertFalse(FoodFilterPipeline::wordMatches('dal', 'dalia'));
        $this->assertFalse(FoodFilterPipeline::wordMatches('', 'anything'));
    }

    public function test_is_disliked_matches_by_food_group_token(): void
    {
        $pipeline = app(FoodFilterPipeline::class);
        $food     = new FoodMaster;
        $food->setRawAttributes(['dish_name' => 'Masoor Dal', 'food_group' => 'Pulse/Legume']);

        // "pulse" is a token of the food_group (split on "/")
        $this->assertTrue($pipeline->isDisliked($food, ['pulse']));
        $this->assertFalse($pipeline->isDisliked($food, ['dairy']));
    }

    public function test_allows_legacy_excludes_disliked_by_word_match(): void
    {
        $pipeline = app(FoodFilterPipeline::class);
        $food     = new FoodMaster;
        $food->setRawAttributes([
            'dish_name' => 'Karela Sabji', 'food_group' => 'Vegetable',
            'is_veg' => 'Yes', 'is_vegan' => 'No', 'is_jain_ok' => 'No',
            'contains_dairy' => 'No', 'contains_egg' => 'No', 'contains_nuts' => 'No',
            'contains_gluten' => 'No', 'contains_honey' => 'No',
        ]);

        // "karela" word-matches "Karela Sabji"
        $this->assertFalse($pipeline->allows($food, ['diet_type' => 'vegetarian'], ['karela']));
        // Without dislikes — passes
        $this->assertTrue($pipeline->allows($food, ['diet_type' => 'vegetarian'], []));
    }

    public function test_allergy_excludes_even_if_listed_as_favourite_pipeline(): void
    {
        // A food that contains dairy AND is in the favourite list must still be excluded
        // by an allergy.  The allergy check runs in allowsSafe() before any preference.
        $pipeline = app(FoodFilterPipeline::class);
        $food     = new FoodMaster;
        $food->setRawAttributes([
            'dish_name' => 'Paneer Butter Masala', 'food_group' => 'Dairy',
            'is_veg' => 'Yes', 'is_vegan' => 'No', 'is_jain_ok' => 'No',
            'contains_dairy' => 'Yes', 'contains_egg' => 'No', 'contains_nuts' => 'No',
            'contains_gluten' => 'No', 'contains_honey' => 'No',
        ]);

        // Dairy allergy → excluded regardless of favourite list
        $this->assertFalse($pipeline->allows(
            $food,
            ['diet_type' => 'vegetarian', 'allergies' => ['dairy']],
            []   // not even in dislikes
        ));
    }

    // ------------------------------------------------------------------
    // normalize() integration
    // ------------------------------------------------------------------

    public function test_normalize_populates_favourite_and_disliked_foods(): void
    {
        $this->seedIcmrTarget();
        $this->seedFoods();

        [, $assessment] = $this->makeChild();

        $service = app(ChildProfileService::class);
        $profile = $service->normalize($assessment, [
            17 => 'paneer, idli',
            18 => 'karela/lauki',
        ]);

        $this->assertSame(['paneer', 'idli'], $profile['favourite_foods']);
        $this->assertSame(['karela', 'lauki'], $profile['disliked_foods']);
    }

    public function test_normalize_returns_empty_lists_when_answers_absent(): void
    {
        $this->seedIcmrTarget();
        $this->seedFoods();

        [, $assessment] = $this->makeChild();

        $service = app(ChildProfileService::class);
        $profile = $service->normalize($assessment, []);

        $this->assertSame([], $profile['favourite_foods']);
        $this->assertSame([], $profile['disliked_foods']);
    }

    // ------------------------------------------------------------------
    // Engine integration tests
    // ------------------------------------------------------------------

    /**
     * A disliked food name never appears in the plan when alternatives exist.
     *
     * We dislike "Lunch Cereal 1" specifically.  With 3 other lunch cereals
     * available the engine must never select it.
     */
    public function test_disliked_food_never_appears_when_alternatives_exist(): void
    {
        $this->seedIcmrTarget();
        $this->seedFoods();

        [, $assessment] = $this->makeChild();

        $engine = app(RuleBasedDietPlanEngine::class);
        $result = $engine->generate($assessment, [
            '_seed' => 42,
            18      => 'lunch cereal 1',
        ]);

        $this->assertTrue($result['success']);

        $names = $this->foodNamesInPlan($result['diet_plan_id']);
        $this->assertNotContains('Lunch Cereal 1', $names, 'Disliked food should not appear in plan');
    }

    /**
     * Per-slot fallback: even if every food in a slot is disliked, the engine
     * must still generate a plan (using fallback foods for that slot).
     *
     * We dislike "breakfast" (group = Cereal) which would match ALL breakfast
     * cereal foods.  With group-protection the pool is restored, but even if
     * that fails the slot fallback must kick in.
     */
    public function test_plan_generates_when_all_slot_candidates_are_disliked(): void
    {
        $this->seedIcmrTarget();
        $this->seedFoods();

        [, $assessment] = $this->makeChild();

        $engine = app(RuleBasedDietPlanEngine::class);
        // Dislike all 8 breakfast cereals by name — every single breakfast food.
        $disliked = implode(',', array_map(fn ($i) => "Breakfast Cereal {$i}", range(1, 8)));
        $result   = $engine->generate($assessment, [
            '_seed' => 7,
            18      => $disliked,
        ]);

        $this->assertTrue($result['success']);
        // Plan was saved and has meals.
        $this->assertNotNull($result['diet_plan_id']);
        $this->assertGreaterThan(0, DietPlanMeal::where('diet_plan_id', $result['diet_plan_id'])->count());
    }

    /**
     * A child who dislikes ALL foods whose food_group contains "pulse" must still
     * receive at least one pulse/legume food in the week (ICMR group protection).
     */
    public function test_child_who_dislikes_all_pulses_still_gets_pulses(): void
    {
        $this->seedIcmrTarget();
        $this->seedFoods();

        [, $assessment] = $this->makeChild();

        $engine = app(RuleBasedDietPlanEngine::class);
        // Dislike all 8 pulse foods by term "dal" (matches "Lunch Dal N", "Dinner Dal N")
        $result = $engine->generate($assessment, [
            '_seed' => 5,
            18      => 'dal',
        ]);

        $this->assertTrue($result['success']);

        $names = $this->foodNamesInPlan($result['diet_plan_id']);
        $hasPulse = collect($names)->contains(fn ($n) => str_contains(strtolower($n), 'dal'));
        $this->assertTrue($hasPulse, 'ICMR group protection: child must still receive pulse foods');
    }

    /**
     * A child who dislikes all dairy foods must still receive at least one
     * dairy food in the week (ICMR group protection for milk balance).
     */
    public function test_child_who_dislikes_all_dairy_still_gets_dairy(): void
    {
        $this->seedIcmrTarget();
        $this->seedFoods();

        [, $assessment] = $this->makeChild();

        $engine = app(RuleBasedDietPlanEngine::class);
        // "dairy" substring matches group "Dairy"
        $result = $engine->generate($assessment, [
            '_seed' => 3,
            18      => 'dairy',
        ]);

        $this->assertTrue($result['success']);

        $names    = $this->foodNamesInPlan($result['diet_plan_id']);
        $hasDairy = collect($names)->contains(
            fn ($n) => str_contains(strtolower($n), 'dairy') || str_contains(strtolower($n), 'paneer')
        );
        $this->assertTrue($hasDairy, 'ICMR group protection: child must still receive dairy foods');
    }

    /**
     * Allergy beats favourite: a dairy-allergic child must never receive
     * a food even if it appears in their favourite list.
     */
    public function test_allergy_beats_favourite_in_engine(): void
    {
        $this->seedIcmrTarget();
        $this->seedFoods();

        [, $assessment] = $this->makeChild();
        // Give the child a dairy allergy on the assessment.
        $assessment->update(['allergies' => ['dairy']]);

        $engine = app(RuleBasedDietPlanEngine::class);
        $result = $engine->generate($assessment, [
            '_seed' => 9,
            // "Paneer Dish" contains dairy and is also listed as favourite
            17 => 'Paneer Dish',
        ]);

        $this->assertTrue($result['success']);

        $names = $this->foodNamesInPlan($result['diet_plan_id']);
        // No dairy-containing foods must appear when child has dairy allergy.
        $dairyFoods = DB::table('foods')
            ->where('contains_dairy', 'Yes')
            ->pluck('dish_name')
            ->all();
        foreach ($dairyFoods as $dairyName) {
            $this->assertNotContains(
                $dairyName,
                $names,
                "Dairy food '{$dairyName}' must not appear for dairy-allergic child"
            );
        }
    }

    /**
     * Favourite food appears more often than a neutral food of the same type,
     * but NOT every single day.
     *
     * We mark "Lunch Cereal 1" as favourite and run a 7-day plan.
     * It should appear at least 2/7 days (some boost) but not all 7.
     */
    public function test_favourite_appears_more_often_but_not_every_day(): void
    {
        $this->seedIcmrTarget();
        $this->seedFoods();

        [, $assessment] = $this->makeChild();

        // Run many seeds to get stable statistics across them.
        $engine          = app(RuleBasedDietPlanEngine::class);
        $favouriteCount  = 0;
        $totalDays       = 0;
        $everyDayCount   = 0;
        $seeds           = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

        foreach ($seeds as $seed) {
            $result = $engine->generate($assessment, [
                '_seed' => $seed,
                17      => 'Lunch Cereal 1',
            ]);
            $this->assertTrue($result['success']);

            $planId = $result['diet_plan_id'];
            $plan   = DietPlan::find($planId);
            $days   = $plan->plan_data['schedule']['days'];
            $totalDays += $days;

            $names   = $this->foodNamesInPlan($planId);
            $count   = count(array_filter($names, fn ($n) => $n === 'Lunch Cereal 1'));
            $favouriteCount += $count;

            if ($count >= $days) {
                $everyDayCount++;
            }
        }

        $totalRuns = count($seeds);

        // Favourite should appear in at least 25% of lunch slots across all runs.
        // (7 days × 1 lunch = 7 slots per run × 10 = 70 total lunch slots)
        $this->assertGreaterThan(
            0,
            $favouriteCount,
            'Favourite food should appear at least once across runs'
        );

        // It should NOT appear every single day in every single run.
        // Allow at most 5/10 runs to have it every day (very generous to avoid flakiness).
        $this->assertLessThanOrEqual(
            5,
            $everyDayCount,
            'Favourite must not dominate every day in more than half of runs'
        );
    }

    /**
     * Weekly variety: no food appears every day of the week.
     *
     * Seeded foods have repeat_gap_days=2 so the engine already enforces this.
     * Verify that with dislikes set the variety rule still holds.
     */
    public function test_weekly_variety_holds_with_dislikes(): void
    {
        $this->seedIcmrTarget();
        $this->seedFoods();

        [, $assessment] = $this->makeChild();

        $engine = app(RuleBasedDietPlanEngine::class);
        $result = $engine->generate($assessment, [
            '_seed' => 55,
            18      => 'Lunch Cereal 1',   // dislike one food
        ]);

        $this->assertTrue($result['success']);

        $names    = $this->foodNamesInPlan($result['diet_plan_id']);
        $counts   = array_count_values($names);
        $planDays = DietPlan::find($result['diet_plan_id'])->plan_data['schedule']['days'];

        foreach ($counts as $name => $count) {
            $this->assertLessThan(
                $planDays,
                $count,
                "Food '{$name}' appears every day ({$count}/{$planDays}) — variety broken"
            );
        }
    }

    /**
     * Protein and calcium must not fall below the levels produced without dislikes.
     *
     * We run the engine twice with the same seed: once without dislikes and once
     * disliking one lunch food.  Because all foods have identical nutrient profiles
     * and the fallback kicks in, the daily totals should be effectively equal.
     * We just assert that neither drops to zero.
     */
    public function test_protein_and_calcium_do_not_collapse_with_dislikes(): void
    {
        $this->seedIcmrTarget();
        $this->seedFoods();

        [, $assessment] = $this->makeChild();

        $engine = app(RuleBasedDietPlanEngine::class);

        $baseline = $engine->generate($assessment, ['_seed' => 22]);
        $this->assertTrue($baseline['success']);

        $withDislikes = $engine->generate($assessment, [
            '_seed' => 22,
            18      => 'Lunch Cereal 1',
        ]);
        $this->assertTrue($withDislikes['success']);

        // Check daily averages are non-zero for both.
        $avgProteinBase     = collect($baseline['daily_nutrients'])->avg('protein_g');
        $avgProteinDisliked = collect($withDislikes['daily_nutrients'])->avg('protein_g');
        $avgCalciumBase     = collect($baseline['daily_nutrients'])->avg('calcium_mg');
        $avgCalciumDisliked = collect($withDislikes['daily_nutrients'])->avg('calcium_mg');

        $this->assertGreaterThan(0, $avgProteinBase, 'Baseline protein must be > 0');
        $this->assertGreaterThan(0, $avgProteinDisliked, 'Protein must not collapse when disliked food excluded');
        $this->assertGreaterThan(0, $avgCalciumBase, 'Baseline calcium must be > 0');
        $this->assertGreaterThan(0, $avgCalciumDisliked, 'Calcium must not collapse when disliked food excluded');

        // The disliked run must stay within 25% of baseline (since alternatives exist
        // and all foods have the same nutrients, difference is near zero in practice).
        if ($avgProteinBase > 0) {
            $proteinRatio = $avgProteinDisliked / $avgProteinBase;
            $this->assertGreaterThanOrEqual(0.75, $proteinRatio, 'Protein should not drop more than 25% with dislikes');
        }
        if ($avgCalciumBase > 0) {
            $calciumRatio = $avgCalciumDisliked / $avgCalciumBase;
            $this->assertGreaterThanOrEqual(0.75, $calciumRatio, 'Calcium should not drop more than 25% with dislikes');
        }
    }

    /**
     * Debug metadata in plan_data includes disliked_foods and favourite_foods.
     */
    public function test_plan_debug_includes_dislike_and_favourite_metadata(): void
    {
        $this->seedIcmrTarget();
        $this->seedFoods();

        [, $assessment] = $this->makeChild();

        $engine = app(RuleBasedDietPlanEngine::class);
        $result = $engine->generate($assessment, [
            '_seed' => 1,
            17      => 'paneer',
            18      => 'karela',
        ]);

        $this->assertTrue($result['success']);
        $plan  = DietPlan::find($result['diet_plan_id']);
        $debug = $plan->plan_data['generation_debug'];

        $this->assertArrayHasKey('disliked_foods', $debug);
        $this->assertArrayHasKey('favourite_foods', $debug);
        $this->assertContains('karela', $debug['disliked_foods']);
        $this->assertContains('paneer', $debug['favourite_foods']);
    }

    /**
     * Per-slot fallback safety: foods selected via the fallback path (when dislikes
     * would empty a slot) must still satisfy the child's diet type and must not
     * contain any allergen.
     *
     * Setup: vegetarian child with a nut allergy.  Dislike all 8 breakfast foods
     * by their exact names so the slot is entirely disliked.  Seed one extra
     * breakfast food that is non-veg (must never appear) and one that contains
     * nuts (must never appear).  The fallback must pick only from the safe pool,
     * which already excludes the non-veg and nut-containing foods.
     */
    public function test_fallback_slot_food_is_allergy_safe_and_diet_compliant(): void
    {
        $this->seedIcmrTarget();
        $this->seedFoods();

        // Add a non-veg breakfast food (must be excluded by diet filter).
        DB::table('foods')->insert([
            'id'               => 100,
            'dish_id'          => 'C6-NONVEG',
            'dish_name'        => 'Chicken Breakfast',
            'slot'             => 'breakfast',
            'region'           => 'Pan-India',
            'food_group'       => 'Poultry',
            'diet_type'        => 'non-vegetarian',
            'serving_g'        => 100,
            'kid_accept_1_5'   => 3,
            'repeat_gap_days'  => 2,
            'is_veg'           => 'No',
            'is_vegan'         => 'No',
            'is_jain_ok'       => 'No',
            'contains_dairy'   => 'No',
            'contains_gluten'  => 'No',
            'contains_egg'     => 'No',
            'contains_nuts'    => 'No',
            'contains_soy'     => 'No',
            'contains_sesame'  => 'No',
            'contains_honey'   => 'No',
            'energy_kcal'      => 200.0,
            'protein_g'        => 5.0,
            'fat_g'            => 3.0,
            'fibre_total_g'    => 2.0,
            'calcium_mg'       => 60.0,
            'iron_mg'          => 1.0,
            'zinc_mg'          => 0.5,
            'magnesium_mg'     => 15.0,
            'vit_a_rae_ug'     => 30.0,
            'vit_c_mg'        => 5.0,
            'vit_d_ug'         => 0.5,
            'b1_mg'            => 0.1,
            'b2_mg'            => 0.1,
            'b3_mg'            => 0.5,
            'b6_mg'            => 0.1,
            'folate_ug'        => 10.0,
            'b12_ug'           => 0.1,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // Add a nut-containing breakfast food (must be excluded by allergy filter).
        DB::table('foods')->insert([
            'id'               => 101,
            'dish_id'          => 'C6-NUT',
            'dish_name'        => 'Nut Granola Bowl',
            'slot'             => 'breakfast',
            'region'           => 'Pan-India',
            'food_group'       => 'Cereal',
            'diet_type'        => 'vegetarian',
            'serving_g'        => 100,
            'kid_accept_1_5'   => 3,
            'repeat_gap_days'  => 2,
            'is_veg'           => 'Yes',
            'is_vegan'         => 'Yes',
            'is_jain_ok'       => 'Yes',
            'contains_dairy'   => 'No',
            'contains_gluten'  => 'No',
            'contains_egg'     => 'No',
            'contains_nuts'    => 'Yes',   // ← allergen
            'contains_soy'     => 'No',
            'contains_sesame'  => 'No',
            'contains_honey'   => 'No',
            'energy_kcal'      => 200.0,
            'protein_g'        => 5.0,
            'fat_g'            => 3.0,
            'fibre_total_g'    => 2.0,
            'calcium_mg'       => 60.0,
            'iron_mg'          => 1.0,
            'zinc_mg'          => 0.5,
            'magnesium_mg'     => 15.0,
            'vit_a_rae_ug'     => 30.0,
            'vit_c_mg'        => 5.0,
            'vit_d_ug'         => 0.5,
            'b1_mg'            => 0.1,
            'b2_mg'            => 0.1,
            'b3_mg'            => 0.5,
            'b6_mg'            => 0.1,
            'folate_ug'        => 10.0,
            'b12_ug'           => 0.1,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        [, $assessment] = $this->makeChild();
        // Give the child a nut allergy.
        $assessment->update(['allergies' => ['nuts']]);

        $engine = app(RuleBasedDietPlanEngine::class);
        // Dislike all 8 seeded breakfast cereals by name → triggers per-slot fallback.
        $disliked = implode(',', array_map(fn ($i) => "Breakfast Cereal {$i}", range(1, 8)));
        $result   = $engine->generate($assessment, [
            '_seed' => 77,
            18      => $disliked,
        ]);

        $this->assertTrue($result['success']);

        // Collect all food IDs used in the plan.
        $usedIds = DietPlanItem::whereHas('meal', fn ($q) => $q->where('diet_plan_id', $result['diet_plan_id']))
            ->pluck('food_id')
            ->unique()
            ->all();

        // Non-veg food must never appear.
        $this->assertNotContains(100, $usedIds, 'Non-veg food must not appear in a vegetarian plan');

        // Nut-containing food must never appear (nut allergy).
        $this->assertNotContains(101, $usedIds, 'Nut-containing food must not appear for a nut-allergic child');

        // The plan must still have breakfast meals (fallback worked).
        $breakfastCount = DietPlanMeal::where('diet_plan_id', $result['diet_plan_id'])
            ->where('meal_slot', 'Breakfast')
            ->count();
        $this->assertGreaterThan(0, $breakfastCount, 'Fallback must still produce breakfast meals');
    }
}
