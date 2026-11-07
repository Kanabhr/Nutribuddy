<?php

namespace Tests\Unit\Nutrition;

use App\Models\Nutrition\FoodMaster;
use App\Services\Nutrition\FoodFilterPipeline;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FoodFilterPipeline::hasAllergen (via allows()).
 *
 * Covers:
 *  - lactose excludes foods with contains_dairy = Yes (same as 'dairy')
 *  - soy / sesame / honey allergen tokens
 *  - NULL flag with a matching allergy → food is excluded (fail-safe)
 *  - NULL flag WITHOUT a matching allergy → food is allowed
 */
class AllergenFilterTest extends TestCase
{
    private FoodFilterPipeline $pipeline;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pipeline = new FoodFilterPipeline;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /** Build a FoodMaster instance with raw attributes (no DB). */
    private function food(array $attributes): FoodMaster
    {
        $defaults = [
            'dish_name'      => 'Test dish',
            'is_veg'         => 'Yes',
            'is_vegan'       => 'No',
            'is_jain_ok'     => 'No',
            'contains_dairy' => 'No',
            'contains_egg'   => 'No',
            'contains_nuts'  => 'No',
            'contains_gluten'=> 'No',
            'contains_honey' => 'No',
            'contains_soy'   => 'No',
            'contains_sesame'=> 'No',
            'diet_type'      => 'Veg',
            'food_group'     => 'Grain',
            'slot'           => 'Breakfast',
        ];

        $food = new FoodMaster;
        $food->setRawAttributes(array_merge($defaults, $attributes));

        return $food;
    }

    private function profile(array $allergies): array
    {
        return ['diet_type' => 'vegetarian', 'allergies' => $allergies];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // lactose
    // ──────────────────────────────────────────────────────────────────────────

    public function test_lactose_allergy_excludes_dairy_food(): void
    {
        $dairy = $this->food(['contains_dairy' => 'Yes']);

        $this->assertFalse(
            $this->pipeline->allows($dairy, $this->profile(['lactose'])),
            'A food with contains_dairy=Yes must be excluded for a child with a lactose allergy.'
        );
    }

    public function test_lactose_allergy_allows_non_dairy_food(): void
    {
        $noDairy = $this->food(['contains_dairy' => 'No']);

        $this->assertTrue(
            $this->pipeline->allows($noDairy, $this->profile(['lactose'])),
            'A food with contains_dairy=No must be allowed for a child with a lactose allergy.'
        );
    }

    public function test_dairy_allergy_still_excludes_dairy_food(): void
    {
        $dairy = $this->food(['contains_dairy' => 'Yes']);

        $this->assertFalse($this->pipeline->allows($dairy, $this->profile(['dairy'])));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // soy
    // ──────────────────────────────────────────────────────────────────────────

    public function test_soy_allergy_excludes_soy_food(): void
    {
        $soy = $this->food(['contains_soy' => 'Yes']);

        $this->assertFalse($this->pipeline->allows($soy, $this->profile(['soy'])));
    }

    public function test_soy_allergy_allows_soy_free_food(): void
    {
        $noSoy = $this->food(['contains_soy' => 'No']);

        $this->assertTrue($this->pipeline->allows($noSoy, $this->profile(['soy'])));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // sesame
    // ──────────────────────────────────────────────────────────────────────────

    public function test_sesame_allergy_excludes_sesame_food(): void
    {
        $sesame = $this->food(['contains_sesame' => 'Yes']);

        $this->assertFalse($this->pipeline->allows($sesame, $this->profile(['sesame'])));
    }

    public function test_sesame_allergy_allows_sesame_free_food(): void
    {
        $noSesame = $this->food(['contains_sesame' => 'No']);

        $this->assertTrue($this->pipeline->allows($noSesame, $this->profile(['sesame'])));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // honey
    // ──────────────────────────────────────────────────────────────────────────

    public function test_honey_allergy_excludes_honey_food(): void
    {
        $honey = $this->food(['contains_honey' => 'Yes']);

        $this->assertFalse($this->pipeline->allows($honey, $this->profile(['honey'])));
    }

    public function test_honey_allergy_allows_honey_free_food(): void
    {
        $noHoney = $this->food(['contains_honey' => 'No']);

        $this->assertTrue($this->pipeline->allows($noHoney, $this->profile(['honey'])));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // NULL flag behaviour (fail-safe)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * When contains_soy is NULL (not yet verified by the data team) and the child
     * has a soy allergy, the food must be excluded to protect the child.
     */
    public function test_null_flag_with_matching_allergy_excludes_food(): void
    {
        // Deliberately omit contains_soy so it is absent from raw attributes (→ null).
        $food = new FoodMaster;
        $food->setRawAttributes([
            'dish_name'      => 'Unknown soy food',
            'is_veg'         => 'Yes',
            'is_vegan'       => 'No',
            'is_jain_ok'     => 'No',
            'contains_dairy' => 'No',
            'contains_egg'   => 'No',
            'contains_nuts'  => 'No',
            'contains_gluten'=> 'No',
            'contains_honey' => 'No',
            // contains_soy intentionally absent
            'contains_sesame'=> 'No',
            'diet_type'      => 'Veg',
            'food_group'     => 'Legume',
            'slot'           => 'Lunch',
        ]);

        $this->assertFalse(
            $this->pipeline->allows($food, $this->profile(['soy'])),
            'A food with an unverified (NULL) soy flag must be excluded for a soy-allergic child.'
        );
    }

    /**
     * When contains_soy is NULL but the child has NO soy allergy, the food passes
     * the allergen check (it may still be filtered by diet rules, but not by soy).
     */
    public function test_null_flag_without_matching_allergy_allows_food(): void
    {
        $food = new FoodMaster;
        $food->setRawAttributes([
            'dish_name'      => 'Unknown soy food',
            'is_veg'         => 'Yes',
            'is_vegan'       => 'No',
            'is_jain_ok'     => 'No',
            'contains_dairy' => 'No',
            'contains_egg'   => 'No',
            'contains_nuts'  => 'No',
            'contains_gluten'=> 'No',
            'contains_honey' => 'No',
            // contains_soy intentionally absent
            'contains_sesame'=> 'No',
            'diet_type'      => 'Veg',
            'food_group'     => 'Legume',
            'slot'           => 'Lunch',
        ]);

        // Child has a sesame allergy but NOT a soy allergy.
        $this->assertTrue(
            $this->pipeline->allows($food, $this->profile(['sesame'])),
            'A food with an unverified soy flag must still be allowed if the child has no soy allergy.'
        );
    }

    /**
     * NULL lactose/dairy flag with lactose allergy → excluded.
     */
    public function test_null_dairy_flag_with_lactose_allergy_excludes_food(): void
    {
        $food = new FoodMaster;
        $food->setRawAttributes([
            'dish_name'      => 'Unverified dairy dish',
            'is_veg'         => 'Yes',
            'is_vegan'       => 'No',
            'is_jain_ok'     => 'No',
            // contains_dairy intentionally absent (NULL)
            'contains_egg'   => 'No',
            'contains_nuts'  => 'No',
            'contains_gluten'=> 'No',
            'contains_honey' => 'No',
            'contains_soy'   => 'No',
            'contains_sesame'=> 'No',
            'diet_type'      => 'Veg',
            'food_group'     => 'Dairy',
            'slot'           => 'Breakfast',
        ]);

        $this->assertFalse(
            $this->pipeline->allows($food, $this->profile(['lactose'])),
            'A food with an unverified dairy flag must be excluded for a lactose-allergic child.'
        );
    }
}
