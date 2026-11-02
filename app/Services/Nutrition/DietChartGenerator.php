<?php

namespace App\Services\Nutrition;

use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DietPlan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DietChartGenerator
{
    protected $promptBuilder;

    public function __construct(AIPromptBuilder $promptBuilder)
    {
        $this->promptBuilder = $promptBuilder;
    }

    /**
     * The final step of the pipeline that interfaces with the AI.
     */
    public function generate(
        ChildAssessment $assessment, 
        array $dailyMeals, 
        array $adequacyAnalysis, 
        $tips
    ) {
        $child = $assessment->child;
        $prompt = $this->promptBuilder->build($child, $assessment, $dailyMeals, $adequacyAnalysis, $tips);
        return $this->generateFromPrompt($assessment, $prompt);
    }

    public function generateFromPrompt(ChildAssessment $assessment, string $prompt)
    {
        // 1. Call LLM (Placeholder for OpenAI/Gemini/Anthropic API call)
        $aiResponse = $this->callLLM($prompt);
        
        // 2. Parse AI Response (expecting structured JSON)
        $parsed = json_decode($aiResponse, true);
        
        // 3. Save to DB (DietPlan)
        $dietPlan = DietPlan::create([
            'child_assessment_id' => $assessment->id,
            'ai_summary' => $parsed['summary'] ?? '',
            'plan_data' => json_encode($parsed),
            'version' => 1,
            'status' => 'pending_review',
        ]);
        
        // Return the parsed JSON response for the frontend to render immediately
        return $parsed;
    }

    private function callLLM($prompt)
    {
        $apiKey = env('OPENAI_API_KEY');
        
        if (empty($apiKey)) {
            Log::warning('OPENAI_API_KEY is not set. Using fallback mock data.');
            return $this->getMockData();
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o',
                    'response_format' => [ 'type' => 'json_object' ],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an expert pediatric nutritionist and Ayurvedic consultant. Output MUST be strictly valid JSON matching this structure exactly: 
{
  "summary": "Brief 1-sentence overview",
  "plan": {
    "Monday": { "Breakfast": {"name": "...", "calories": 200, "protein": 10}, "Morning Snack": {...}, "Lunch": {...}, "Evening Snack": {...}, "Dinner": {...} },
    "Tuesday": { ... },
    "Wednesday": { ... },
    "Thursday": { ... },
    "Friday": { ... },
    "Saturday": { ... },
    "Sunday": { ... }
  }
}
Generate varied, healthy Indian/Ayurvedic meals based on the provided child profile. Calories and protein MUST be integers.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ]
                ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                return $content;
            }

            Log::error('OpenAI API Error: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('OpenAI Exception: ' . $e->getMessage());
        }
        
        return $this->getMockData();
    }

    private function getMockData()
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $mockMeals = [
            'Breakfast' => [
                ['name' => 'Oatmeal with Berries', 'calories' => 250, 'protein' => 8],
                ['name' => 'Scrambled Eggs on Toast', 'calories' => 300, 'protein' => 14],
            ],
            'Morning Snack' => [
                ['name' => 'Apple Slices with Peanut Butter', 'calories' => 150, 'protein' => 4],
                ['name' => 'Greek Yogurt', 'calories' => 120, 'protein' => 10],
            ],
            'Lunch' => [
                ['name' => 'Grilled Chicken Salad', 'calories' => 400, 'protein' => 25],
                ['name' => 'Quinoa Bowl with Veggies', 'calories' => 380, 'protein' => 12],
            ],
            'Evening Snack' => [
                ['name' => 'Carrot Sticks with Hummus', 'calories' => 130, 'protein' => 3],
                ['name' => 'Cheese String', 'calories' => 80, 'protein' => 6],
            ],
            'Dinner' => [
                ['name' => 'Baked Salmon with Asparagus', 'calories' => 450, 'protein' => 30],
                ['name' => 'Spaghetti Bolognese', 'calories' => 500, 'protein' => 22],
            ]
        ];

        $plan = [];
        foreach ($days as $index => $day) {
            $dailyPlan = [];
            foreach (['Breakfast', 'Morning Snack', 'Lunch', 'Evening Snack', 'Dinner'] as $mealTime) {
                $options = $mockMeals[$mealTime];
                $dailyPlan[$mealTime] = $options[$index % count($options)];
            }
            $plan[$day] = $dailyPlan;
        }

        return json_encode([
            'summary' => 'This diet focuses on healthy weight gain and increasing iron intake. *Note: Nutrition values are estimates based on standard servings.*',
            'plan' => $plan,
            'tips' => []
        ]);
    }
}
