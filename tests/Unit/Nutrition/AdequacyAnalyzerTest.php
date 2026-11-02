<?php

namespace Tests\Unit\Nutrition;

use PHPUnit\Framework\TestCase;
use App\Services\Nutrition\AdequacyAnalyzer;

class AdequacyAnalyzerTest extends TestCase
{
    public function test_it_calculates_adequacy_correctly()
    {
        $analyzer = new AdequacyAnalyzer();

        // Mock sum of what the child actually ate (from NutritionCalculator)
        $achieved = [
            'energy_kcal' => 1950,
            'protein_g' => 30, // 30g out of 32.8 target = 91.46% (Green)
            'calcium_mg' => 400, // 400mg out of 850 target = 47% (Deficient)
            'iron_mg' => 35, // 35mg out of 28 target = 125% (Excess)
        ];

        // Mock ICMR Target (e.g. 10-12 year old girl)
        $target = (object)[
            'energy_kcal' => 2060,
            'protein_g' => 32.8,
            'calcium_mg' => 850,
            'iron_mg' => 28,
            'fat_g' => 35,
            'fiber_g' => 25
        ];

        $results = $analyzer->analyze($achieved, $target);

        // Assert Protein is Adequate (Green)
        $this->assertEquals('Adequate (Green)', $results['protein_g']['status']);
        $this->assertEquals(91.46, $results['protein_g']['adequacy_percent']);

        // Assert Calcium is Deficient
        $this->assertEquals('Deficient', $results['calcium_mg']['status']);
        
        // Assert Iron is Excess
        $this->assertEquals('Excess', $results['iron_mg']['status']);
    }
}
