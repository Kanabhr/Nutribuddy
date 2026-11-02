<?php

namespace Tests\Unit\Nutrition;

use App\Models\Nutrition\FoodMaster;
use App\Services\Nutrition\FoodFilterPipeline;
use App\Services\Nutrition\DietChartGeneratorService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class FoodFilterPipelineTest extends TestCase
{
    private FoodFilterPipeline $filters;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filters = app(FoodFilterPipeline::class);
    }

    public function test_hard_diet_and_allergy_restrictions_cannot_be_overridden(): void
    {
        $safeVeg = $this->food(['is_veg' => 'Yes']);
        $egg = $this->food(['is_veg' => 'Yes', 'contains_egg' => 'Yes']);
        $dairy = $this->food(['is_veg' => 'Yes', 'contains_dairy' => 'Yes']);
        $nut = $this->food(['is_veg' => 'Yes', 'contains_nuts' => 'Yes']);
        $gluten = $this->food(['is_veg' => 'Yes', 'contains_gluten' => 'Yes']);
        $jain = $this->food(['is_veg' => 'Yes', 'is_jain_ok' => 'Yes']);

        $this->assertTrue($this->filters->allows($safeVeg, ['diet_type' => 'vegetarian']));
        $this->assertFalse($this->filters->allows($egg, ['diet_type' => 'vegetarian']));
        $this->assertFalse($this->filters->allows($dairy, ['diet_type' => 'vegetarian', 'allergies' => ['dairy']]));
        $this->assertFalse($this->filters->allows($nut, ['diet_type' => 'vegetarian', 'allergies' => ['nuts']]));
        $this->assertFalse($this->filters->allows($gluten, ['diet_type' => 'vegetarian', 'allergies' => ['gluten']]));
        $this->assertTrue($this->filters->allows($jain, ['diet_type' => 'jain']));
        $this->assertFalse($this->filters->allows($safeVeg, ['diet_type' => 'jain']));
    }

    public function test_vegan_excludes_animal_derived_flags(): void
    {
        $vegan = $this->food(['is_vegan' => 'Yes']);
        $dairy = $this->food(['is_vegan' => 'Yes', 'contains_dairy' => 'Yes']);
        $honey = $this->food(['is_vegan' => 'Yes', 'contains_honey' => 'Yes']);

        $this->assertTrue($this->filters->allows($vegan, ['diet_type' => 'vegan']));
        $this->assertFalse($this->filters->allows($dairy, ['diet_type' => 'vegan']));
        $this->assertFalse($this->filters->allows($honey, ['diet_type' => 'vegan']));
    }

    public function test_slot_filter_uses_primary_and_alternative_slots(): void
    {
        $foods = new Collection([
            $this->food(['slot' => 'Breakfast']),
            $this->food(['slot' => 'Lunch', 'also_suitable_for' => 'Snack, Dinner']),
        ]);

        $this->assertCount(1, $this->filters->forSlot($foods, 'Evening Snack'));
        $this->assertCount(1, $this->filters->forSlot($foods, 'Breakfast'));
    }

    public function test_rule_based_generator_is_resolvable(): void
    {
        $this->assertInstanceOf(DietChartGeneratorService::class, app(DietChartGeneratorService::class));
    }

    private function food(array $attributes): FoodMaster
    {
        $food = new FoodMaster;
        $food->setRawAttributes(array_merge([
            'dish_name' => 'Database Dish', 'is_veg' => 'No', 'is_vegan' => 'No', 'is_jain_ok' => 'No',
            'contains_dairy' => 'No', 'contains_egg' => 'No', 'contains_nuts' => 'No', 'contains_gluten' => 'No', 'contains_honey' => 'No',
            'diet_type' => 'Veg', 'food_group' => 'Grain', 'slot' => 'Breakfast',
        ], $attributes));
        return $food;
    }
}
