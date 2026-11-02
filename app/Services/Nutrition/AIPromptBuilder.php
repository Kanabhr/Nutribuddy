<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\Child;

class AIPromptBuilder
{
    /**
     * Builds a strict prompt for the LLM based on deterministic math.
     */
    public function build(Child $child, ChildAssessment $assessment, array $dailyMeals, array $adequacyAnalysis, $tips)
    {
        $prompt = "You are a pediatric nutritionist AI. Your ONLY job is to format the deterministic diet plan provided below into a friendly, readable schedule and explain the tips.\n";
        $prompt .= "DO NOT invent foods, change portions, or make medical diagnoses.\n\n";
        
        $prompt .= "1. CHILD PROFILE\n";
        $prompt .= "- Age: " . \Carbon\Carbon::parse($child->dob)->age . "\n";
        $prompt .= "- Goal: {$assessment->main_goal}\n";
        $prompt .= "- Diet Type: {$assessment->diet_type}\n\n";
        
        $prompt .= "2. APPROVED DAILY MEALS (Use these exact foods and servings)\n";
        foreach ($dailyMeals as $slot => $foods) {
            $prompt .= "- {$slot}:\n";
            foreach ($foods as $food) {
                $prompt .= "  * {$food->food_name} ({$food->serving})\n";
                if (!empty($food->method)) {
                    $prompt .= "    Recipe Prep: " . json_encode($food->method) . "\n";
                }
            }
        }
        $prompt .= "\n";
        
        $prompt .= "3. NUTRITION ADEQUACY SUMMARY (For your explanation)\n";
        foreach ($adequacyAnalysis as $nutrient => $data) {
            $prompt .= "- {$nutrient}: {$data['status']} ({$data['adequacy_percent']}% of ICMR Target)\n";
        }
        $prompt .= "\n";
        
        $prompt .= "4. TIPS & SMART COMBINATIONS\n";
        foreach ($tips as $tip) {
            $prompt .= "- Benefit: {$tip->benefit} -> Combine: {$tip->combination}. (Science: {$tip->science_reason})\n";
        }
        $prompt .= "\n";
        
        $prompt .= "OUTPUT FORMAT: Return a JSON object with 'summary' (friendly overview), 'meals' (array of meal objects with time, name, portion, recipe), and 'tips' (array of strings).\n";
        $prompt .= "Remember: State that nutrition values are estimates based on standard servings, not clinical lab-grade precision.";
        
        return $prompt;
    }
}
