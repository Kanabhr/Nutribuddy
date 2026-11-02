<?php

namespace App\Services\Nutrition;

class AdequacyAnalyzer
{
    /**
     * Compares summed daily nutrition against ICMR targets.
     * Generates a 90-110% green score.
     */
    public function analyze($summedNutrition, $icmrTarget)
    {
        $analysis = [];
        
        $nutrientsToCheck = [
            'energy_kcal', 'protein_g', 'fat_g', 'fiber_g', 'calcium_mg', 'iron_mg'
            // Add all other required metrics
        ];

        foreach ($nutrientsToCheck as $nutrient) {
            $achieved = $summedNutrition[$nutrient] ?? 0;
            $target = $icmrTarget->{$nutrient} ?? 1; // avoid div by 0
            
            $adequacyPercent = ($achieved / $target) * 100;
            
            $status = 'Deficient';
            if ($adequacyPercent >= 90 && $adequacyPercent <= 110) {
                $status = 'Adequate (Green)';
            } elseif ($adequacyPercent > 110) {
                $status = 'Excess';
            }

            $analysis[$nutrient] = [
                'target' => $target,
                'achieved' => $achieved,
                'adequacy_percent' => round($adequacyPercent, 2),
                'status' => $status
            ];
        }

        return $analysis;
    }
}
