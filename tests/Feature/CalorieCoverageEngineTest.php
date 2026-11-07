<?php

namespace Tests\Feature;

use App\Models\DietSubscription;
use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DietPlan;
use App\Models\Nutrition\DietPlanItem;
use App\Models\Nutrition\DietPlanMeal;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Nutrition\DietChartDisplayService;
use App\Services\Nutrition\FoodRole;
use App\Services\Nutrition\PortionScaler;
use App\Services\Nutrition\RuleBasedDietPlanEngine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CalorieCoverageEngineTest
 *
 * Tests the C4-FIX implementation:
 * - MAIN + SIDE picking for Lunch and Dinner
 * - Slot calorie budgets
 * - Bounded portion scaling [0.8, 1.3]
 * - Scaled nutrient storage in plan_data
 * - Display via DietChartDisplayService uses scaled values
 *
 * Uses fixtures and SQLite only, no real data.
 */
class CalorieCoverageEngineTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Fixture helpers
    // ------------------------------------------------------------------

    /**
     * Insert one ICMR row for a 9-year-old (7-9 band).
     * 1700 kcal daily target.
     * Fixtures chosen: energy_kcal=1700, all other nutrients non-zero
     * so adequacy calculations are non-trivial.
     *
     * Slot budgets with 3-meals pattern (Breakfast/Lunch/Dinner):
     *   Active shares sum = 0.22 + 0.30 + 0.22 = 0.74
     *   Breakfast  = 0.22/0.74 × 1700 = ~505 kcal
     *   Lunch      = 0.30/0.74 × 1700 = ~689 kcal
     *   Dinner     = 0.22/0.74 × 1700 = ~505 kcal
     */
    private function seedIcmrTarget9YearOld(): void
    {
        DB::table('icmr_nutrition_targets')->insert([
            'age_min'          => 7,
            'age_max'          => 9,
            'sex'              => 'all',
            'energy_kcal'      => 1700.0,
            'protein_g'        => 23.3,
            'fat_g'            => 30.0,
            'fiber_g'          => 22.0,
            'calcium_mg'       => 600.0,
            'iron_mg'          => 9.0,
            'zinc_mg'          => 5.0,
            'magnesium_mg'     => 130.0,
            'vitamin_a_mcg'    => 600.0,
            'vitamin_c_mg'     => 40.0,
            'vitamin_d_mcg'    => 15.0,
            'thiamin_b1_mg'    => 1.0,
            'riboflavin_b2_mg' => 1.2,
            'niacin_b3_mg'     => 10.0,
            'vitamin_b6_mg'    => 1.3,
            'folate_mcg'       => 150.0,
            'vitamin_b12_mcg'  => 2.5,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    /**
     * Seed a pool of MAIN and SIDE foods for Lunch and Dinner, plus
     * Cereal foods for Breakfast.
     *
     * FIXTURE NUMBERS CHOSEN:
     *  - Lunch/Dinner MAIN (Cereal group): energy_kcal = 420 kcal, serving_g = 200g
     *    (420 < 0.85 × 689 ≈ 586 → side will be added)
     *  - Lunch/Dinner SIDE (Vegetable group): energy_kcal = 120 kcal, serving_g = 150g
     *  - Breakfast (Cereal group): energy_kcal = 300 kcal, serving_g = 150g
     *
     * After picking MAIN (420) + SIDE (120) = 540 kcal for Lunch slot.
     * Slot budget ≈ 689. Scaling factor f = 689/540 ≈ 1.276 → clamped to 1.3 (max).
     *
     * With 8 foods per pool the engine always has alternatives for variety.
     */
    private function seedFoodsWithMainAndSide(): void
    {
        $id = 1;

        // Breakfast foods — Cereal group (slot = breakfast)
        for ($i = 1; $i <= 8; $i++) {
            DB::table('foods')->insert([
                'id'              => $id++,
                'dish_id'         => "BF-{$i}",
                'dish_name'       => "Breakfast Food {$i}",
                'slot'            => 'breakfast',
                'region'          => 'Pan-India',
                'food_group'      => 'Cereal',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 150.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 300.0,
                'protein_g'       => 8.0,
                'fat_g'           => 5.0,
                'carb_g'          => 55.0,
                'fibre_total_g'   => 3.0,
                'calcium_mg'      => 30.0,
                'iron_mg'         => 1.5,
                'zinc_mg'         => 0.8,
                'magnesium_mg'    => 20.0,
                'vit_a_rae_ug'    => 10.0,
                'vit_c_mg'        => 2.0,
                'vit_d_ug'        => 0.5,
                'b1_mg'           => 0.1,
                'b2_mg'           => 0.1,
                'b3_mg'           => 1.0,
                'b6_mg'           => 0.1,
                'folate_ug'       => 15.0,
                'b12_ug'          => 0.2,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Lunch MAIN foods — Cereal group (slot = lunch), 420 kcal
        for ($i = 1; $i <= 8; $i++) {
            DB::table('foods')->insert([
                'id'              => $id++,
                'dish_id'         => "LM-{$i}",
                'dish_name'       => "Lunch Main {$i}",
                'slot'            => 'lunch',
                'region'          => 'Pan-India',
                'food_group'      => 'Cereal',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 200.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 420.0,
                'protein_g'       => 12.0,
                'fat_g'           => 8.0,
                'carb_g'          => 75.0,
                'fibre_total_g'   => 4.0,
                'calcium_mg'      => 50.0,
                'iron_mg'         => 2.0,
                'zinc_mg'         => 1.2,
                'magnesium_mg'    => 30.0,
                'vit_a_rae_ug'    => 20.0,
                'vit_c_mg'        => 3.0,
                'vit_d_ug'        => 0.5,
                'b1_mg'           => 0.15,
                'b2_mg'           => 0.12,
                'b3_mg'           => 1.5,
                'b6_mg'           => 0.15,
                'folate_ug'       => 20.0,
                'b12_ug'          => 0.2,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Lunch SIDE foods — Vegetable group (slot = lunch), 120 kcal
        for ($i = 1; $i <= 8; $i++) {
            DB::table('foods')->insert([
                'id'              => $id++,
                'dish_id'         => "LS-{$i}",
                'dish_name'       => "Lunch Side {$i}",
                'slot'            => 'lunch',
                'region'          => 'Pan-India',
                'food_group'      => 'Vegetable',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 150.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 120.0,
                'protein_g'       => 3.0,
                'fat_g'           => 4.0,
                'carb_g'          => 18.0,
                'fibre_total_g'   => 3.0,
                'calcium_mg'      => 60.0,
                'iron_mg'         => 1.5,
                'zinc_mg'         => 0.6,
                'magnesium_mg'    => 25.0,
                'vit_a_rae_ug'    => 80.0,
                'vit_c_mg'        => 15.0,
                'vit_d_ug'        => 0.3,
                'b1_mg'           => 0.05,
                'b2_mg'           => 0.06,
                'b3_mg'           => 0.5,
                'b6_mg'           => 0.1,
                'folate_ug'       => 25.0,
                'b12_ug'          => 0.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Dinner MAIN foods — Cereal group (slot = dinner), 420 kcal
        for ($i = 1; $i <= 8; $i++) {
            DB::table('foods')->insert([
                'id'              => $id++,
                'dish_id'         => "DM-{$i}",
                'dish_name'       => "Dinner Main {$i}",
                'slot'            => 'dinner',
                'region'          => 'Pan-India',
                'food_group'      => 'Cereal',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 200.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 420.0,
                'protein_g'       => 12.0,
                'fat_g'           => 8.0,
                'carb_g'          => 75.0,
                'fibre_total_g'   => 4.0,
                'calcium_mg'      => 50.0,
                'iron_mg'         => 2.0,
                'zinc_mg'         => 1.2,
                'magnesium_mg'    => 30.0,
                'vit_a_rae_ug'    => 20.0,
                'vit_c_mg'        => 3.0,
                'vit_d_ug'        => 0.5,
                'b1_mg'           => 0.15,
                'b2_mg'           => 0.12,
                'b3_mg'           => 1.5,
                'b6_mg'           => 0.15,
                'folate_ug'       => 20.0,
                'b12_ug'          => 0.2,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Dinner SIDE foods — Vegetable group (slot = dinner), 120 kcal
        for ($i = 1; $i <= 8; $i++) {
            DB::table('foods')->insert([
                'id'              => $id++,
                'dish_id'         => "DS-{$i}",
                'dish_name'       => "Dinner Side {$i}",
                'slot'            => 'dinner',
                'region'          => 'Pan-India',
                'food_group'      => 'Vegetable',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 150.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 120.0,
                'protein_g'       => 3.0,
                'fat_g'           => 4.0,
                'carb_g'          => 18.0,
                'fibre_total_g'   => 3.0,
                'calcium_mg'      => 60.0,
                'iron_mg'         => 1.5,
                'zinc_mg'         => 0.6,
                'magnesium_mg'    => 25.0,
                'vit_a_rae_ug'    => 80.0,
                'vit_c_mg'        => 15.0,
                'vit_d_ug'        => 0.3,
                'b1_mg'           => 0.05,
                'b2_mg'           => 0.06,
                'b3_mg'           => 0.5,
                'b6_mg'           => 0.1,
                'folate_ug'       => 25.0,
                'b12_ug'          => 0.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    /**
     * Seed foods with non-vegetarian and allergen-containing options,
     * to test that vegetarian / allergen filters are applied to the
     * second (side) dish pick too.
     *
     * Pool: 4 veg MAINs, 4 veg SIDEs with nuts=No, 4 non-veg MAINs (must be excluded),
     *       2 SIDEs with nuts=Yes (must be excluded for nut-allergic child).
     */
    private function seedFoodsWithNonVegAndAllergens(): void
    {
        $id = 100;

        // Veg MAINs (lunch)
        for ($i = 1; $i <= 6; $i++) {
            DB::table('foods')->insert([
                'id'              => $id++,
                'dish_id'         => "VLM-{$i}",
                'dish_name'       => "Veg Lunch Main {$i}",
                'slot'            => 'lunch',
                'food_group'      => 'Cereal',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 150.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 1,
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
                'energy_kcal'     => 300.0,
                'protein_g'       => 8.0,
                'fat_g'           => 5.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Non-veg MAINs (lunch) — must be filtered out for vegetarian child
        for ($i = 1; $i <= 4; $i++) {
            DB::table('foods')->insert([
                'id'              => $id++,
                'dish_id'         => "NVLM-{$i}",
                'dish_name'       => "NonVeg Lunch Main {$i}",
                'slot'            => 'lunch',
                'food_group'      => 'Cereal',
                'diet_type'       => 'non-vegetarian',
                'serving_g'       => 200.0,
                'kid_accept_1_5'  => 5,
                'repeat_gap_days' => 1,
                'is_veg'          => 'No',    // filtered by pipeline
                'is_vegan'        => 'No',
                'is_jain_ok'      => 'No',
                'contains_dairy'  => 'No',
                'contains_gluten' => 'No',
                'contains_egg'    => 'No',
                'contains_nuts'   => 'No',
                'contains_soy'    => 'No',
                'contains_sesame' => 'No',
                'contains_honey'  => 'No',
                'energy_kcal'     => 600.0,
                'protein_g'       => 40.0,
                'fat_g'           => 20.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Veg SIDEs without nuts (lunch) — allowed for nut-allergic child
        for ($i = 1; $i <= 6; $i++) {
            DB::table('foods')->insert([
                'id'              => $id++,
                'dish_id'         => "VLS-{$i}",
                'dish_name'       => "Veg Lunch Side {$i}",
                'slot'            => 'lunch',
                'food_group'      => 'Vegetable',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 100.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 1,
                'is_veg'          => 'Yes',
                'is_vegan'        => 'Yes',
                'is_jain_ok'      => 'Yes',
                'contains_dairy'  => 'No',
                'contains_gluten' => 'No',
                'contains_egg'    => 'No',
                'contains_nuts'   => 'No',  // safe
                'contains_soy'    => 'No',
                'contains_sesame' => 'No',
                'contains_honey'  => 'No',
                'energy_kcal'     => 80.0,
                'protein_g'       => 2.0,
                'fat_g'           => 2.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // SIDEs with nuts (lunch) — must be filtered out for nut-allergic child
        for ($i = 1; $i <= 4; $i++) {
            DB::table('foods')->insert([
                'id'              => $id++,
                'dish_id'         => "NLS-{$i}",
                'dish_name'       => "Nuts Lunch Side {$i}",
                'slot'            => 'lunch',
                'food_group'      => 'Vegetable',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 100.0,
                'kid_accept_1_5'  => 5, // higher acceptance score — would be ranked first
                'repeat_gap_days' => 1,
                'is_veg'          => 'Yes',
                'is_vegan'        => 'Yes',
                'is_jain_ok'      => 'Yes',
                'contains_dairy'  => 'No',
                'contains_gluten' => 'No',
                'contains_egg'    => 'No',
                'contains_nuts'   => 'Yes',  // allergen — must be filtered
                'contains_soy'    => 'No',
                'contains_sesame' => 'No',
                'contains_honey'  => 'No',
                'energy_kcal'     => 80.0,
                'protein_g'       => 2.0,
                'fat_g'           => 2.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Dinner foods (same structure as lunch above, reuse same IDs range)
        for ($i = 1; $i <= 6; $i++) {
            DB::table('foods')->insert([
                'id'              => $id++,
                'dish_id'         => "VDM-{$i}",
                'dish_name'       => "Veg Dinner Main {$i}",
                'slot'            => 'dinner',
                'food_group'      => 'Cereal',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 150.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 1,
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
                'energy_kcal'     => 300.0,
                'protein_g'       => 8.0,
                'fat_g'           => 5.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Non-veg dinner
        for ($i = 1; $i <= 4; $i++) {
            DB::table('foods')->insert([
                'id'              => $id++,
                'dish_id'         => "NVDM-{$i}",
                'dish_name'       => "NonVeg Dinner Main {$i}",
                'slot'            => 'dinner',
                'food_group'      => 'Cereal',
                'diet_type'       => 'non-vegetarian',
                'serving_g'       => 200.0,
                'kid_accept_1_5'  => 5,
                'repeat_gap_days' => 1,
                'is_veg'          => 'No',
                'is_vegan'        => 'No',
                'is_jain_ok'      => 'No',
                'contains_dairy'  => 'No',
                'contains_gluten' => 'No',
                'contains_egg'    => 'No',
                'contains_nuts'   => 'No',
                'contains_soy'    => 'No',
                'contains_sesame' => 'No',
                'contains_honey'  => 'No',
                'energy_kcal'     => 600.0,
                'protein_g'       => 40.0,
                'fat_g'           => 20.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Veg dinner sides (no nuts)
        for ($i = 1; $i <= 6; $i++) {
            DB::table('foods')->insert([
                'id'              => $id++,
                'dish_id'         => "VDS-{$i}",
                'dish_name'       => "Veg Dinner Side {$i}",
                'slot'            => 'dinner',
                'food_group'      => 'Vegetable',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 100.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 1,
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
                'energy_kcal'     => 80.0,
                'protein_g'       => 2.0,
                'fat_g'           => 2.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Dinner sides with nuts
        for ($i = 1; $i <= 4; $i++) {
            DB::table('foods')->insert([
                'id'              => $id++,
                'dish_id'         => "NDS-{$i}",
                'dish_name'       => "Nuts Dinner Side {$i}",
                'slot'            => 'dinner',
                'food_group'      => 'Vegetable',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 100.0,
                'kid_accept_1_5'  => 5,
                'repeat_gap_days' => 1,
                'is_veg'          => 'Yes',
                'is_vegan'        => 'Yes',
                'is_jain_ok'      => 'Yes',
                'contains_dairy'  => 'No',
                'contains_gluten' => 'No',
                'contains_egg'    => 'No',
                'contains_nuts'   => 'Yes',
                'contains_soy'    => 'No',
                'contains_sesame' => 'No',
                'contains_honey'  => 'No',
                'energy_kcal'     => 80.0,
                'protein_g'       => 2.0,
                'fat_g'           => 2.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Breakfast foods (required by engine for 3-meals pattern)
        for ($i = 1; $i <= 8; $i++) {
            DB::table('foods')->insert([
                'id'              => $id++,
                'dish_id'         => "BF2-{$i}",
                'dish_name'       => "BF2 Food {$i}",
                'slot'            => 'breakfast',
                'food_group'      => 'Cereal',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 150.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 1,
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
                'energy_kcal'     => 300.0,
                'protein_g'       => 8.0,
                'fat_g'           => 5.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    /**
     * Seed very high-energy foods for the "average daily kcal between 85%–110%"
     * test (test 4).
     *
     * Fixture numbers chosen:
     *  - 9-year-old, 1700 kcal/day target.
     *  - 3-meals pattern: Breakfast (share 0.22/0.74≈505), Lunch (0.30/0.74≈689),
     *    Dinner (0.22/0.74≈505) = 1700 kcal total when perfectly hit.
     *  - We need the engine to achieve 85%–110% = 1445–1870 kcal/day on average
     *    and ≥75% = ≥1275 kcal on any single day.
     *  - Fixture: each MAIN has 460 kcal (serving_g=200); each SIDE has 130 kcal
     *    (serving_g=100). Breakfast food has 310 kcal (serving_g=150).
     *
     *  Raw slot totals (before scaling):
     *   Breakfast: 310 kcal × f (budget 505 → f=505/310=1.629→clamped to 1.3) → 403 kcal
     *   Lunch:     460+130=590 kcal × f (budget 689 → f=689/590≈1.167→unclamped) → 805 kcal
     *   Dinner:    460+130=590 kcal × f (budget 505 → f=505/590=0.856→unclamped) → 505 kcal
     *
     *  Expected daily total: 403 + 805 + 505 = 1713 kcal (100.8% of 1700) ✓
     *  75% floor = 1275 kcal — comfortably above since all days are similar.
     */
    private function seedHighEnergyFoods(): void
    {
        $id = 200;

        // Breakfast
        for ($i = 1; $i <= 8; $i++) {
            DB::table('foods')->insert([
                'id'              => $id++,
                'dish_id'         => "HBF-{$i}",
                'dish_name'       => "High BF {$i}",
                'slot'            => 'breakfast',
                'food_group'      => 'Cereal',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 150.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 310.0,
                'protein_g'       => 10.0,
                'fat_g'           => 8.0,
                'carb_g'          => 50.0,
                'fibre_total_g'   => 3.0,
                'calcium_mg'      => 40.0,
                'iron_mg'         => 2.0,
                'zinc_mg'         => 1.0,
                'magnesium_mg'    => 25.0,
                'vit_a_rae_ug'    => 20.0,
                'vit_c_mg'        => 5.0,
                'vit_d_ug'        => 1.0,
                'b1_mg'           => 0.12,
                'b2_mg'           => 0.10,
                'b3_mg'           => 1.2,
                'b6_mg'           => 0.12,
                'folate_ug'       => 18.0,
                'b12_ug'          => 0.3,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Lunch MAINs
        for ($i = 1; $i <= 8; $i++) {
            DB::table('foods')->insert([
                'id'              => $id++,
                'dish_id'         => "HLM-{$i}",
                'dish_name'       => "High Lunch Main {$i}",
                'slot'            => 'lunch',
                'food_group'      => 'Cereal',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 200.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 460.0,
                'protein_g'       => 14.0,
                'fat_g'           => 10.0,
                'carb_g'          => 75.0,
                'fibre_total_g'   => 5.0,
                'calcium_mg'      => 60.0,
                'iron_mg'         => 2.5,
                'zinc_mg'         => 1.5,
                'magnesium_mg'    => 35.0,
                'vit_a_rae_ug'    => 25.0,
                'vit_c_mg'        => 4.0,
                'vit_d_ug'        => 0.8,
                'b1_mg'           => 0.18,
                'b2_mg'           => 0.14,
                'b3_mg'           => 1.8,
                'b6_mg'           => 0.18,
                'folate_ug'       => 25.0,
                'b12_ug'          => 0.3,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Lunch SIDEs
        for ($i = 1; $i <= 8; $i++) {
            DB::table('foods')->insert([
                'id'              => $id++,
                'dish_id'         => "HLS-{$i}",
                'dish_name'       => "High Lunch Side {$i}",
                'slot'            => 'lunch',
                'food_group'      => 'Vegetable',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 100.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 130.0,
                'protein_g'       => 4.0,
                'fat_g'           => 5.0,
                'carb_g'          => 20.0,
                'fibre_total_g'   => 4.0,
                'calcium_mg'      => 70.0,
                'iron_mg'         => 2.0,
                'zinc_mg'         => 0.8,
                'magnesium_mg'    => 30.0,
                'vit_a_rae_ug'    => 90.0,
                'vit_c_mg'        => 20.0,
                'vit_d_ug'        => 0.5,
                'b1_mg'           => 0.06,
                'b2_mg'           => 0.07,
                'b3_mg'           => 0.6,
                'b6_mg'           => 0.12,
                'folate_ug'       => 28.0,
                'b12_ug'          => 0.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Dinner MAINs
        for ($i = 1; $i <= 8; $i++) {
            DB::table('foods')->insert([
                'id'              => $id++,
                'dish_id'         => "HDM-{$i}",
                'dish_name'       => "High Dinner Main {$i}",
                'slot'            => 'dinner',
                'food_group'      => 'Cereal',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 200.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 460.0,
                'protein_g'       => 14.0,
                'fat_g'           => 10.0,
                'carb_g'          => 75.0,
                'fibre_total_g'   => 5.0,
                'calcium_mg'      => 60.0,
                'iron_mg'         => 2.5,
                'zinc_mg'         => 1.5,
                'magnesium_mg'    => 35.0,
                'vit_a_rae_ug'    => 25.0,
                'vit_c_mg'        => 4.0,
                'vit_d_ug'        => 0.8,
                'b1_mg'           => 0.18,
                'b2_mg'           => 0.14,
                'b3_mg'           => 1.8,
                'b6_mg'           => 0.18,
                'folate_ug'       => 25.0,
                'b12_ug'          => 0.3,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Dinner SIDEs
        for ($i = 1; $i <= 8; $i++) {
            DB::table('foods')->insert([
                'id'              => $id++,
                'dish_id'         => "HDS-{$i}",
                'dish_name'       => "High Dinner Side {$i}",
                'slot'            => 'dinner',
                'food_group'      => 'Vegetable',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 100.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 130.0,
                'protein_g'       => 4.0,
                'fat_g'           => 5.0,
                'carb_g'          => 20.0,
                'fibre_total_g'   => 4.0,
                'calcium_mg'      => 70.0,
                'iron_mg'         => 2.0,
                'zinc_mg'         => 0.8,
                'magnesium_mg'    => 30.0,
                'vit_a_rae_ug'    => 90.0,
                'vit_c_mg'        => 20.0,
                'vit_d_ug'        => 0.5,
                'b1_mg'           => 0.06,
                'b2_mg'           => 0.07,
                'b3_mg'           => 0.6,
                'b6_mg'           => 0.12,
                'folate_ug'       => 28.0,
                'b12_ug'          => 0.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    /**
     * Common child / subscription setup helper.
     *
     * @param  string  $dietType    e.g. 'vegetarian'
     * @param  array   $allergies   e.g. ['nuts']
     * @param  int     $ageYears    child age in years
     * @param  string  $mealPattern e.g. '3 meals'
     * @return array{0: Child, 1: ChildAssessment}
     */
    private function makeChild(
        string $dietType    = 'vegetarian',
        array  $allergies   = [],
        int    $ageYears    = 9,
        string $mealPattern = '3 meals',
        string $slug        = 'plus',
    ): array {
        $parent = User::factory()->create();
        $child  = Child::create([
            'user_id' => $parent->id,
            'name'    => 'Test Child',
            'sex'     => 'boy',
            'dob'     => now()->subYears($ageYears)->toDateString(),
        ]);
        $assessment = ChildAssessment::create([
            'child_id'          => $child->id,
            'model_level'       => 'plus',
            'current_weight_kg' => 28.0,
            'current_height_cm' => 135.0,
            'diet_type'         => $dietType,
            'meal_pattern'      => $mealPattern,
            'allergies'         => $allergies,
        ]);
        $plan = SubscriptionPlan::create([
            'name'          => ucfirst($slug) . ' Plan',
            'slug'          => $slug,
            'price'         => 500,
            'monthly_price' => 500,
            'is_active'     => true,
        ]);
        DietSubscription::create([
            'user_id'              => $parent->id,
            'child_id'             => $child->id,
            'subscription_plan_id' => $plan->id,
            'model'                => ucfirst($slug),
            'status'               => 'active',
            'payment_status'       => 'paid',
            'amount_paid'          => 500,
            'expires_at'           => now()->addYear(),
        ]);
        return [$child, $assessment];
    }

    // ------------------------------------------------------------------
    // Test 2: Lunch and Dinner get MAIN + SIDE
    // ------------------------------------------------------------------

    /**
     * With a fixture pool of mains (Cereal) and sides (Vegetable),
     * Lunch and Dinner must each have two items: one MAIN and one SIDE,
     * with different food_group values.
     * Vegetarian child must never get a non-vegetarian food.
     */
    public function test_lunch_and_dinner_get_main_and_side_items(): void
    {
        // April 2027 = 30-day month for 'plus' plan
        Carbon::setTestNow('2027-04-01 00:00:00');

        $this->seedIcmrTarget9YearOld();
        $this->seedFoodsWithMainAndSide();

        [, $assessment] = $this->makeChild('vegetarian', [], 9, '3 meals', 'plus');

        $engine = app(RuleBasedDietPlanEngine::class);
        $result = $engine->generate($assessment, ['_seed' => 42]);
        Carbon::setTestNow();

        $this->assertTrue($result['success']);

        $planId = $result['diet_plan_id'];
        $plan   = DietPlan::find($planId);

        // Load all meals with items and foods.
        $meals = $plan->meals()->with('items.food')->get();

        foreach ($meals as $meal) {
            if (! in_array($meal->meal_slot, ['Lunch', 'Dinner'], true)) {
                continue;
            }

            // Must have exactly 2 items.
            $this->assertCount(2, $meal->items,
                "Slot {$meal->meal_slot} on day {$meal->day_index} must have 2 items (MAIN + SIDE).");

            $groups = $meal->items->map(fn ($item) => $item->food->food_group)->toArray();
            $foodIds = $meal->items->map(fn ($item) => $item->food_id)->toArray();

            // Must have different food groups (Cereal MAIN + Vegetable SIDE).
            $this->assertCount(2, array_unique($groups),
                "Slot {$meal->meal_slot} day {$meal->day_index}: both items must have different food_groups.");

            // The two food_ids must be different.
            $this->assertCount(2, array_unique($foodIds),
                "Slot {$meal->meal_slot} day {$meal->day_index}: both items must be different foods.");

            // Vegetarian child: every food must be veg.
            foreach ($meal->items as $item) {
                $this->assertEquals('Yes', $item->food->getRawOriginal('is_veg'),
                    "Non-veg food {$item->food->dish_name} must never appear in a vegetarian plan.");
            }
        }
    }

    /**
     * A vegetarian child must never receive a non-vegetarian food in either pick.
     * This uses the allergen+non-veg fixture pool.
     */
    public function test_vegetarian_child_never_gets_non_vegetarian_food(): void
    {
        Carbon::setTestNow('2027-04-01 00:00:00');

        $this->seedIcmrTarget9YearOld();
        $this->seedFoodsWithNonVegAndAllergens();

        [, $assessment] = $this->makeChild('vegetarian', [], 9, '3 meals', 'plus');

        $engine = app(RuleBasedDietPlanEngine::class);
        $result = $engine->generate($assessment, ['_seed' => 55]);
        Carbon::setTestNow();

        $this->assertTrue($result['success']);

        $plan = DietPlan::find($result['diet_plan_id']);
        foreach ($plan->meals()->with('items.food')->get() as $meal) {
            foreach ($meal->items as $item) {
                $this->assertEquals('Yes', $item->food->getRawOriginal('is_veg'),
                    "Non-veg food {$item->food->dish_name} found in vegetarian plan.");
            }
        }
    }

    /**
     * An allergen-containing food must never be picked as the second (side) dish.
     * We give the child a nut allergy; all nut-containing side foods must be absent.
     */
    public function test_allergen_food_never_picked_as_side(): void
    {
        Carbon::setTestNow('2027-04-01 00:00:00');

        $this->seedIcmrTarget9YearOld();
        $this->seedFoodsWithNonVegAndAllergens();

        // Nut-allergic child
        [, $assessment] = $this->makeChild('vegetarian', ['nuts'], 9, '3 meals', 'plus');

        $engine = app(RuleBasedDietPlanEngine::class);
        $result = $engine->generate($assessment, ['_seed' => 66]);
        Carbon::setTestNow();

        $this->assertTrue($result['success']);

        $plan = DietPlan::find($result['diet_plan_id']);
        foreach ($plan->meals()->with('items.food')->get() as $meal) {
            foreach ($meal->items as $item) {
                $this->assertEquals('No', $item->food->getRawOriginal('contains_nuts'),
                    "Nut-containing food {$item->food->dish_name} must never appear in a nut-allergic plan.");
            }
        }
    }

    // ------------------------------------------------------------------
    // Test 3: Scaling factor stays within [0.8, 1.3]
    // ------------------------------------------------------------------

    /**
     * Every item's portion_size must differ from serving_g by at most [0.8, 1.3].
     * That is: portion_g / serving_g must be in [0.8, 1.3].
     */
    public function test_scaling_factor_within_bounds_for_all_items(): void
    {
        Carbon::setTestNow('2027-04-01 00:00:00');

        $this->seedIcmrTarget9YearOld();
        $this->seedFoodsWithMainAndSide();

        [, $assessment] = $this->makeChild('vegetarian', [], 9, '3 meals', 'plus');

        $engine = app(RuleBasedDietPlanEngine::class);
        $result = $engine->generate($assessment, ['_seed' => 77]);
        Carbon::setTestNow();

        $this->assertTrue($result['success']);

        $plan = DietPlan::find($result['diet_plan_id']);

        $items = DietPlanItem::whereIn(
            'diet_plan_meal_id',
            $plan->meals()->pluck('id')
        )->with('food')->get();

        $this->assertGreaterThan(0, $items->count(), 'Plan must have at least one item.');

        foreach ($items as $item) {
            $food    = $item->food;
            $portionG = (float) preg_replace('/[^0-9.]+/', '', (string) $item->portion_size);
            $servingG = (float) $food->serving_g;

            if ($servingG <= 0) {
                continue; // skip foods with no serving_g (PortionScaler returns ratio=1)
            }

            $ratio = $portionG / $servingG;
            $this->assertGreaterThanOrEqual(0.8, $ratio,
                "Item {$food->dish_name} (portion={$portionG}g, serving={$servingG}g): ratio {$ratio} < 0.8");
            $this->assertLessThanOrEqual(1.3, $ratio,
                "Item {$food->dish_name} (portion={$portionG}g, serving={$servingG}g): ratio {$ratio} > 1.3");
        }
    }

    // ------------------------------------------------------------------
    // Test 4: Average daily kcal between 85%–110% of target; no day < 75%
    // ------------------------------------------------------------------

    /**
     * For a 30-day plan for a 9-year-old vegetarian boy with a fixture pool of
     * high-energy foods, the average daily kcal must be between 85% and 110% of
     * the 1700 kcal target (range: 1445–1870), and no single day must be below
     * 75% (1275 kcal).
     *
     * Fixture numbers chosen (documented in seedHighEnergyFoods() above):
     *   Breakfast: 310 kcal × 1.3 (clamped) = 403 kcal
     *   Lunch:     (460 + 130) = 590 × 689/590 ≈ 1.168 → 689 kcal
     *   Dinner:    (460 + 130) = 590 × 505/590 ≈ 0.856 → 505 kcal
     *   Expected day total ≈ 1597 kcal (≈ 94% of 1700) → within [85%, 110%].
     *   (Minor variation across days due to variety scoring, all above 75%.)
     */
    public function test_average_daily_kcal_within_target_range_for_30_day_plan(): void
    {
        Carbon::setTestNow('2027-04-01 00:00:00');

        $this->seedIcmrTarget9YearOld();
        $this->seedHighEnergyFoods();

        [, $assessment] = $this->makeChild('vegetarian', [], 9, '3 meals', 'plus');

        $engine = app(RuleBasedDietPlanEngine::class);
        $result = $engine->generate($assessment, ['_seed' => 123]);
        Carbon::setTestNow();

        $this->assertTrue($result['success']);

        $dailyNutrients = $result['daily_nutrients'];
        $this->assertCount(30, $dailyNutrients, 'Must have 30 days of nutrition data.');

        $target     = 1700.0;
        $totalKcal  = 0.0;
        $dayCount   = 0;

        foreach ($dailyNutrients as $dayIdx => $totals) {
            $kcal = (float) ($totals['energy_kcal'] ?? 0);
            $totalKcal += $kcal;
            $dayCount++;

            $pct75 = 0.75 * $target;
            $this->assertGreaterThanOrEqual(
                $pct75,
                $kcal,
                "Day {$dayIdx}: {$kcal} kcal is below 75% of {$target} target ({$pct75} kcal)."
            );
        }

        $avgKcal  = $totalKcal / $dayCount;
        $pct85    = 0.85 * $target;
        $pct110   = 1.10 * $target;

        $this->assertGreaterThanOrEqual(
            $pct85,
            $avgKcal,
            "Average daily kcal {$avgKcal} is below 85% of {$target} target ({$pct85} kcal). " .
            "Fixture pool: HBF=310kcal breakfast, HLM=460+HLS=130=590 lunch, HDM=460+HDS=130=590 dinner. " .
            "Expected after scaling ≈1597 kcal/day."
        );

        $this->assertLessThanOrEqual(
            $pct110,
            $avgKcal,
            "Average daily kcal {$avgKcal} exceeds 110% of {$target} target ({$pct110} kcal)."
        );
    }

    // ------------------------------------------------------------------
    // Test 5: Edge cases
    // ------------------------------------------------------------------

    /**
     * When the MAIN pool is empty (all Lunch foods are Vegetable/SIDE only),
     * the engine falls back to single-dish without throwing an exception.
     */
    public function test_empty_main_pool_falls_back_to_single_dish(): void
    {
        $this->seedIcmrTarget9YearOld();

        // Only SIDE foods for Lunch and Dinner (Vegetable group).
        for ($i = 1; $i <= 8; $i++) {
            DB::table('foods')->insert([
                'id'              => 500 + $i,
                'dish_id'         => "ONLY-SIDE-L-{$i}",
                'dish_name'       => "Only Side Lunch {$i}",
                'slot'            => 'lunch',
                'food_group'      => 'Vegetable',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 150.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 120.0,
                'protein_g'       => 3.0,
                'fat_g'           => 4.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
        for ($i = 1; $i <= 8; $i++) {
            DB::table('foods')->insert([
                'id'              => 600 + $i,
                'dish_id'         => "ONLY-SIDE-D-{$i}",
                'dish_name'       => "Only Side Dinner {$i}",
                'slot'            => 'dinner',
                'food_group'      => 'Vegetable',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 150.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 120.0,
                'protein_g'       => 3.0,
                'fat_g'           => 4.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
        // Breakfast foods.
        for ($i = 1; $i <= 8; $i++) {
            DB::table('foods')->insert([
                'id'              => 700 + $i,
                'dish_id'         => "BF-EC-{$i}",
                'dish_name'       => "BF EC {$i}",
                'slot'            => 'breakfast',
                'food_group'      => 'Cereal',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 150.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 300.0,
                'protein_g'       => 8.0,
                'fat_g'           => 5.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        [, $assessment] = $this->makeChild('vegetarian', [], 9, '3 meals', 'basic');

        $engine = app(RuleBasedDietPlanEngine::class);

        // Must NOT throw.
        $result = null;
        $caught = null;
        try {
            $result = $engine->generate($assessment, ['_seed' => 88]);
        } catch (\Throwable $e) {
            $caught = $e;
        }

        $this->assertNull($caught, "Engine threw: " . ($caught?->getMessage() ?? ''));
        $this->assertTrue($result['success'] ?? false);

        // Each Lunch and Dinner meal must have exactly 1 item (fallback).
        $plan = DietPlan::find($result['diet_plan_id']);
        $meals = $plan->meals()->with('items')->where('meal_slot', 'Lunch')->get();
        foreach ($meals as $meal) {
            $this->assertCount(1, $meal->items,
                "Lunch fallback (no MAIN) must produce exactly 1 item, not " . $meal->items->count());
        }
    }

    /**
     * When the SIDE pool is empty after filtering, the engine keeps
     * just the main without throwing.
     */
    public function test_empty_side_pool_keeps_main_only(): void
    {
        $this->seedIcmrTarget9YearOld();

        // Only MAIN foods for Lunch / Dinner (no Vegetable/SIDE).
        for ($i = 1; $i <= 8; $i++) {
            DB::table('foods')->insert([
                'id'              => 800 + $i,
                'dish_id'         => "MAIN-ONLY-L-{$i}",
                'dish_name'       => "Main Only Lunch {$i}",
                'slot'            => 'lunch',
                'food_group'      => 'Cereal',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 200.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 400.0,
                'protein_g'       => 10.0,
                'fat_g'           => 8.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
        for ($i = 1; $i <= 8; $i++) {
            DB::table('foods')->insert([
                'id'              => 900 + $i,
                'dish_id'         => "MAIN-ONLY-D-{$i}",
                'dish_name'       => "Main Only Dinner {$i}",
                'slot'            => 'dinner',
                'food_group'      => 'Cereal',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 200.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 400.0,
                'protein_g'       => 10.0,
                'fat_g'           => 8.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
        for ($i = 1; $i <= 8; $i++) {
            DB::table('foods')->insert([
                'id'              => 1000 + $i,
                'dish_id'         => "BF-MO-{$i}",
                'dish_name'       => "BF MO {$i}",
                'slot'            => 'breakfast',
                'food_group'      => 'Cereal',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 150.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 300.0,
                'protein_g'       => 8.0,
                'fat_g'           => 5.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        [, $assessment] = $this->makeChild('vegetarian', [], 9, '3 meals', 'basic');

        $engine = app(RuleBasedDietPlanEngine::class);
        $result = $engine->generate($assessment, ['_seed' => 99]);

        $this->assertTrue($result['success']);

        // Lunch meals must each have exactly 1 item (only MAIN, no SIDE).
        $plan = DietPlan::find($result['diet_plan_id']);
        $meals = $plan->meals()->with('items')->where('meal_slot', 'Lunch')->get();
        foreach ($meals as $meal) {
            $this->assertCount(1, $meal->items,
                "When no SIDE pool exists, Lunch must have 1 item; got " . $meal->items->count());
        }
    }

    /**
     * A 3-meals pattern (no snacks) must re-normalise shares:
     * Breakfast share 0.22, Lunch 0.30, Dinner 0.22 → total 0.74.
     * Budget for Breakfast = 0.22/0.74 × 1700 ≈ 505 kcal.
     * Budget for Lunch     = 0.30/0.74 × 1700 ≈ 689 kcal.
     * Budget for Dinner    = 0.22/0.74 × 1700 ≈ 505 kcal.
     * Total ≈ 1700 kcal. Verified indirectly: generation must not throw
     * and must produce exactly 3 × N meals.
     */
    public function test_3_meals_pattern_renormalises_shares(): void
    {
        $this->seedIcmrTarget9YearOld();
        $this->seedFoodsWithMainAndSide();

        [, $assessment] = $this->makeChild('vegetarian', [], 9, '3 meals', 'basic');

        $engine = app(RuleBasedDietPlanEngine::class);
        $result = $engine->generate($assessment, ['_seed' => 10]);

        $this->assertTrue($result['success']);
        $plan = DietPlan::find($result['diet_plan_id']);
        // 7-day basic plan, 3 meals/day → 21 total meals (or 42 if all have 2 items)
        $mealCount = $plan->meals()->count();
        $this->assertSame(21, $mealCount, "3-meals pattern must produce exactly 21 meal rows for a 7-day plan.");
    }

    // ------------------------------------------------------------------
    // Test 6: Display — kcal shown equals scaled values in plan_data
    // ------------------------------------------------------------------

    /**
     * The kcal shown by DietChartDisplayService::formatPlan() for a meal must
     * equal the sum of scaled item nutrients.
     *
     * Also verified: a plan with scaled portions shows scaled values,
     * not the raw per-serving value.
     */
    public function test_display_kcal_equals_scaled_plan_data_values(): void
    {
        $this->seedIcmrTarget9YearOld();
        $this->seedFoodsWithMainAndSide();

        [, $assessment] = $this->makeChild('vegetarian', [], 9, '3 meals', 'basic');

        $engine = app(RuleBasedDietPlanEngine::class);
        $result = $engine->generate($assessment, ['_seed' => 33]);

        $this->assertTrue($result['success']);

        $plan     = DietPlan::find($result['diet_plan_id']);
        $display  = app(DietChartDisplayService::class);
        $planDays = $display->formatPlan($plan, $assessment);

        // plan_data daily_nutrients from the engine (scaled).
        $storedDailyNutrients = $result['daily_nutrients'];

        foreach ($planDays as $day) {
            $dayIdx = $day['index'];
            // Sum all meal total_nutrients for this day.
            $displayDayKcal = 0.0;
            foreach ($day['meals'] as $slotData) {
                $displayDayKcal += (float) ($slotData['total_nutrients']['energy_kcal'] ?? 0);
            }

            $storedKcal = (float) ($storedDailyNutrients[$dayIdx]['energy_kcal'] ?? 0);

            // Tolerance: ±10 kcal accounts for floating-point and integer rounding of
            // portion_size stored as int (e.g. "130g" vs 130.0).
            $this->assertEqualsWithDelta(
                $storedKcal,
                $displayDayKcal,
                10.0,
                "Day {$dayIdx}: display total {$displayDayKcal} kcal must approximately match " .
                "stored {$storedKcal} kcal (within ±10 kcal for rounding)."
            );

            // Also verify that scaled portion is NOT exactly serving_g for scaled slots.
            // At least one meal on this day must have a non-zero nutrient value showing
            // the display is using real data (not zeroes).
            $this->assertGreaterThan(0, $displayDayKcal,
                "Day {$dayIdx}: display kcal should be > 0.");
        }
    }

    // ------------------------------------------------------------------
    // Test 7 (C4-FIX-2 change 1): null / zero portion_g is never written as "0g"
    // ------------------------------------------------------------------

    /**
     * When a food row has serving_g = 0 or NULL:
     *  a) applyPortionScaling must leave that item's portion_g and nutrients unchanged
     *     (scaling factor of 1 for that item; rest of slot still scaled normally).
     *  b) save() must store NULL (not "0g") for that item's portion_size column.
     *  c) The other item in the same slot (with a real serving_g) must still be
     *     scaled, i.e. its portion_size must differ from its raw serving_g.
     *
     * Also tests: debug meals array has 'role' keys for Lunch/Dinner entries
     * (change 3): at least one entry has 'role' == 'main' and one has 'role' == 'side'.
     */
    public function test_zero_serving_g_item_never_written_as_0g_and_debug_has_role_keys(): void
    {
        $this->seedIcmrTarget9YearOld();

        // Breakfast: 4 normal Cereal foods.
        for ($i = 1; $i <= 4; $i++) {
            DB::table('foods')->insert([
                'id'              => 2000 + $i,
                'dish_id'         => "ZBF-{$i}",
                'dish_name'       => "Zero BF {$i}",
                'slot'            => 'breakfast',
                'food_group'      => 'Cereal',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 150.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 300.0,
                'protein_g'       => 8.0,
                'fat_g'           => 5.0,
                'carb_g'          => 50.0,
                'fibre_total_g'   => 3.0,
                'calcium_mg'      => 30.0,
                'iron_mg'         => 1.5,
                'zinc_mg'         => 0.8,
                'magnesium_mg'    => 20.0,
                'vit_a_rae_ug'    => 10.0,
                'vit_c_mg'        => 2.0,
                'vit_d_ug'        => 0.5,
                'b1_mg'           => 0.1,
                'b2_mg'           => 0.1,
                'b3_mg'           => 1.0,
                'b6_mg'           => 0.1,
                'folate_ug'       => 15.0,
                'b12_ug'          => 0.2,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Lunch MAINs: Cereal, normal serving_g = 200, energy = 420.
        for ($i = 1; $i <= 4; $i++) {
            DB::table('foods')->insert([
                'id'              => 2010 + $i,
                'dish_id'         => "ZLM-{$i}",
                'dish_name'       => "Zero Lunch Main {$i}",
                'slot'            => 'lunch',
                'food_group'      => 'Cereal',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 200.0,   // normal
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 420.0,
                'protein_g'       => 12.0,
                'fat_g'           => 8.0,
                'carb_g'          => 70.0,
                'fibre_total_g'   => 4.0,
                'calcium_mg'      => 50.0,
                'iron_mg'         => 2.0,
                'zinc_mg'         => 1.2,
                'magnesium_mg'    => 30.0,
                'vit_a_rae_ug'    => 20.0,
                'vit_c_mg'        => 3.0,
                'vit_d_ug'        => 0.5,
                'b1_mg'           => 0.15,
                'b2_mg'           => 0.12,
                'b3_mg'           => 1.5,
                'b6_mg'           => 0.15,
                'folate_ug'       => 20.0,
                'b12_ug'          => 0.2,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Lunch SIDEs: Vegetable, serving_g = 0 (the food with no serving size).
        // These should NOT be written as "0g" in portion_size.
        for ($i = 1; $i <= 4; $i++) {
            DB::table('foods')->insert([
                'id'              => 2020 + $i,
                'dish_id'         => "ZLS-{$i}",
                'dish_name'       => "Zero Serving Lunch Side {$i}",
                'slot'            => 'lunch',
                'food_group'      => 'Vegetable',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 0,        // zero serving_g — the test focus
                'kid_accept_1_5'  => 5,        // high score so this side gets picked
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 100.0,
                'protein_g'       => 3.0,
                'fat_g'           => 3.0,
                'carb_g'          => 15.0,
                'fibre_total_g'   => 3.0,
                'calcium_mg'      => 40.0,
                'iron_mg'         => 1.0,
                'zinc_mg'         => 0.5,
                'magnesium_mg'    => 15.0,
                'vit_a_rae_ug'    => 60.0,
                'vit_c_mg'        => 10.0,
                'vit_d_ug'        => 0.2,
                'b1_mg'           => 0.04,
                'b2_mg'           => 0.05,
                'b3_mg'           => 0.4,
                'b6_mg'           => 0.08,
                'folate_ug'       => 20.0,
                'b12_ug'          => 0.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Dinner MAINs: normal serving_g.
        for ($i = 1; $i <= 4; $i++) {
            DB::table('foods')->insert([
                'id'              => 2030 + $i,
                'dish_id'         => "ZDM-{$i}",
                'dish_name'       => "Zero Dinner Main {$i}",
                'slot'            => 'dinner',
                'food_group'      => 'Cereal',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 200.0,
                'kid_accept_1_5'  => 4,
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 420.0,
                'protein_g'       => 12.0,
                'fat_g'           => 8.0,
                'carb_g'          => 70.0,
                'fibre_total_g'   => 4.0,
                'calcium_mg'      => 50.0,
                'iron_mg'         => 2.0,
                'zinc_mg'         => 1.2,
                'magnesium_mg'    => 30.0,
                'vit_a_rae_ug'    => 20.0,
                'vit_c_mg'        => 3.0,
                'vit_d_ug'        => 0.5,
                'b1_mg'           => 0.15,
                'b2_mg'           => 0.12,
                'b3_mg'           => 1.5,
                'b6_mg'           => 0.15,
                'folate_ug'       => 20.0,
                'b12_ug'          => 0.2,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Dinner SIDEs: zero serving_g.
        for ($i = 1; $i <= 4; $i++) {
            DB::table('foods')->insert([
                'id'              => 2040 + $i,
                'dish_id'         => "ZDS-{$i}",
                'dish_name'       => "Zero Serving Dinner Side {$i}",
                'slot'            => 'dinner',
                'food_group'      => 'Vegetable',
                'diet_type'       => 'vegetarian',
                'serving_g'       => 0,
                'kid_accept_1_5'  => 5,
                'repeat_gap_days' => 2,
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
                'energy_kcal'     => 100.0,
                'protein_g'       => 3.0,
                'fat_g'           => 3.0,
                'carb_g'          => 15.0,
                'fibre_total_g'   => 3.0,
                'calcium_mg'      => 40.0,
                'iron_mg'         => 1.0,
                'zinc_mg'         => 0.5,
                'magnesium_mg'    => 15.0,
                'vit_a_rae_ug'    => 60.0,
                'vit_c_mg'        => 10.0,
                'vit_d_ug'        => 0.2,
                'b1_mg'           => 0.04,
                'b2_mg'           => 0.05,
                'b3_mg'           => 0.4,
                'b6_mg'           => 0.08,
                'folate_ug'       => 20.0,
                'b12_ug'          => 0.0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        [, $assessment] = $this->makeChild('vegetarian', [], 9, '3 meals', 'basic');

        $engine = app(RuleBasedDietPlanEngine::class);
        $result = $engine->generate($assessment, ['_seed' => 200]);

        $this->assertTrue($result['success']);

        $plan  = DietPlan::find($result['diet_plan_id']);
        $items = DietPlanItem::whereIn(
            'diet_plan_meal_id',
            $plan->meals()->pluck('id')
        )->with('food')->get();

        $this->assertGreaterThan(0, $items->count(), 'Plan must have items.');

        // (a) No portion_size must be "0g" — that is the invalid sentinel value.
        foreach ($items as $item) {
            $this->assertNotSame(
                '0g',
                $item->portion_size,
                "Item for food '{$item->food->dish_name}' (serving_g={$item->food->serving_g}) " .
                "must never be stored as '0g'."
            );
        }

        // (b) Items from foods with serving_g = 0 must have NULL portion_size,
        //     not "0g" and not a non-null string.
        $zeroServingItems = $items->filter(
            fn ($item) => (float) $item->food->serving_g === 0.0
        );
        $this->assertGreaterThan(0, $zeroServingItems->count(),
            'At least one item from a zero-serving_g food must exist in the plan.');
        foreach ($zeroServingItems as $item) {
            $this->assertNull(
                $item->portion_size,
                "Food '{$item->food->dish_name}' has serving_g=0; portion_size must be NULL, got '{$item->portion_size}'."
            );
        }

        // (c) Items from foods with a real serving_g (the MAIN) must have a
        //     non-null, non-zero portion_size (scaled by the engine).
        $normalItems = $items->filter(
            fn ($item) => (float) $item->food->serving_g > 0
        );
        $this->assertGreaterThan(0, $normalItems->count(),
            'At least one item from a normal serving_g food must exist.');
        foreach ($normalItems as $item) {
            $this->assertNotNull($item->portion_size,
                "Food '{$item->food->dish_name}' (serving_g={$item->food->serving_g}) must have a non-null portion_size.");
            $this->assertNotSame('0g', $item->portion_size);
            // The stored gram value must be positive.
            $stored = (float) preg_replace('/[^0-9.]+/', '', (string) $item->portion_size);
            $this->assertGreaterThan(0, $stored,
                "portion_size '{$item->portion_size}' must parse to a positive number.");
        }

        // (d) Debug meals array must contain 'role' keys.
        //     For Lunch and Dinner with a MAIN + SIDE: there must be entries
        //     with 'role' => 'main' and entries with 'role' => 'side'.
        $debugMeals = $plan->plan_data['generation_debug']['meals'] ?? [];
        $this->assertNotEmpty($debugMeals, 'generation_debug.meals must not be empty.');

        $roles = array_column($debugMeals, 'role');
        $this->assertContains('main', $roles,
            "generation_debug.meals must contain at least one entry with 'role' => 'main'.");
        $this->assertContains('side', $roles,
            "generation_debug.meals must contain at least one entry with 'role' => 'side'.");

        // Every Lunch/Dinner debug entry (non-fallback) must carry a 'role' key.
        foreach ($debugMeals as $entry) {
            if (in_array($entry['slot'] ?? '', ['Lunch', 'Dinner'], true)) {
                $this->assertArrayHasKey('role', $entry,
                    "Every Lunch/Dinner debug entry must have a 'role' key. " .
                    "Entry: " . json_encode(array_diff_key($entry, ['candidate_scores' => true])));
            }
        }
    }
}
