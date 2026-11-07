<?php

namespace Tests\Unit\Nutrition;

use App\Models\Nutrition\FoodMaster;
use App\Services\Nutrition\PortionScaler;
use Tests\TestCase;

/**
 * PortionScalerTest — tests the portion-scaling helper.
 *
 * PortionScaler::scale(food, portionG) returns all nutrient keys
 * from food->nutrients scaled by (portionG / serving_g).
 * When serving_g is 0, null, or portionG cannot be parsed → ratio = 1.
 */
class PortionScalerTest extends TestCase
{
    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function makeFood(float $servingG, float $energyKcal, float $proteinG = 0.0): FoodMaster
    {
        $food = new FoodMaster();
        $food->setRawAttributes([
            'serving_g'     => $servingG,
            'energy_kcal'   => $energyKcal,
            'protein_g'     => $proteinG,
            'fat_g'         => 0,
            'carb_g'        => 0,
            'fibre_total_g' => 0,
            'free_sugar_g'  => 0,
            'sfa_g'         => 0,
            'mufa_g'        => 0,
            'pufa_g'        => 0,
            'calcium_mg'    => 0,
            'iron_mg'       => 0,
            'zinc_mg'       => 0,
            'magnesium_mg'  => 0,
            'vit_a_rae_ug'  => 0,
            'vit_c_mg'      => 0,
            'vit_d_ug'      => 0,
            'b1_mg'         => 0,
            'b2_mg'         => 0,
            'b3_mg'         => 0,
            'b6_mg'         => 0,
            'folate_ug'     => 0,
            'b12_ug'        => 0,
        ]);
        return $food;
    }

    // ------------------------------------------------------------------
    // ratio() tests
    // ------------------------------------------------------------------

    public function test_ratio_is_portion_over_serving(): void
    {
        $food = $this->makeFood(100.0, 200.0);
        $this->assertEqualsWithDelta(1.5, PortionScaler::ratio($food, 150.0), 0.001);
    }

    public function test_ratio_equals_one_when_serving_g_is_zero(): void
    {
        $food = $this->makeFood(0.0, 200.0);
        $this->assertSame(1.0, PortionScaler::ratio($food, 150.0));
    }

    public function test_ratio_equals_one_when_serving_g_is_missing(): void
    {
        $food = new FoodMaster();
        $food->setRawAttributes(['energy_kcal' => 200.0]);
        $this->assertSame(1.0, PortionScaler::ratio($food, 150.0));
    }

    public function test_ratio_equals_one_when_portion_is_zero(): void
    {
        $food = $this->makeFood(100.0, 200.0);
        $this->assertSame(1.0, PortionScaler::ratio($food, 0.0));
    }

    public function test_ratio_parses_string_with_g_suffix(): void
    {
        $food = $this->makeFood(100.0, 200.0);
        $this->assertEqualsWithDelta(2.17, PortionScaler::ratio($food, '217g'), 0.001);
    }

    public function test_ratio_returns_one_for_unparseable_string(): void
    {
        $food = $this->makeFood(100.0, 200.0);
        $this->assertSame(1.0, PortionScaler::ratio($food, 'n/a'));
    }

    // ------------------------------------------------------------------
    // scale() tests
    // ------------------------------------------------------------------

    public function test_scale_multiplies_energy_kcal_by_ratio(): void
    {
        // serving_g = 100, energy_kcal = 200; portion = 150 → ratio = 1.5
        $food   = $this->makeFood(100.0, 200.0);
        $result = PortionScaler::scale($food, 150.0);

        $this->assertEqualsWithDelta(300.0, $result['energy_kcal'], 0.01);
    }

    public function test_scale_uses_ratio_one_when_serving_g_missing(): void
    {
        $food = new FoodMaster();
        $food->setRawAttributes([
            'energy_kcal' => 100.0,
            'protein_g'   => 10.0,
        ]);

        $result = PortionScaler::scale($food, 200.0);
        // ratio = 1, so values are unchanged
        $this->assertEqualsWithDelta(100.0, $result['energy_kcal'], 0.01);
        $this->assertEqualsWithDelta(10.0, $result['protein_g'], 0.01);
    }

    public function test_scale_with_g_suffix_string(): void
    {
        $food   = $this->makeFood(100.0, 200.0, 10.0);
        $result = PortionScaler::scale($food, '130g'); // ratio = 1.3

        $this->assertEqualsWithDelta(260.0, $result['energy_kcal'], 0.01);
        $this->assertEqualsWithDelta(13.0,  $result['protein_g'],   0.01);
    }

    public function test_scale_returns_array_with_all_nutrient_keys(): void
    {
        $food   = $this->makeFood(100.0, 200.0);
        $result = PortionScaler::scale($food, 100.0);

        // At minimum these keys must exist in the output.
        foreach (['energy_kcal', 'protein_g', 'fat_g', 'calcium_mg', 'iron_mg'] as $key) {
            $this->assertArrayHasKey($key, $result, "Missing key: {$key}");
        }
    }
}
