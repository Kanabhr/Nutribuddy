<?php

namespace App\Services\Nutrition;

use App\Models\DietSubscription;
use App\Models\Nutrition\Child;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\DietPlan;
use Illuminate\Support\Facades\Auth;

class DietChartDisplayService
{
    public function formatSevenDayPlan(DietPlan $dietPlan, ChildAssessment $assessment): array
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        $meals = $dietPlan->meals()
            ->with(['items.food'])
            ->orderBy('sort_order')
            ->get();

        $slotsPerDay = match ($assessment->meal_pattern) {
            '3 meals' => 3,
            '2 + snacks' => 4,
            default => 5,
        };
        $slotOrder = ['Breakfast' => 1, 'Morning Snack' => 2, 'Lunch' => 3, 'Evening Snack' => 4, 'Snack' => 4, 'Dinner' => 5];

        $mealsByDay = [];
        $dayIndex = 0;

        foreach ($meals as $mealIndex => $meal) {
            $dayIndex = intdiv($mealIndex, $slotsPerDay);
            if ($dayIndex >= count($days)) {
                $dayIndex = count($days) - 1;
            }

            $currentDay = $days[$dayIndex];

            if (!isset($mealsByDay[$currentDay])) {
                $mealsByDay[$currentDay] = [];
            }

            $totalNutrients = [
                'energy_kcal' => 0,
                'protein_g' => 0,
                'carb_g' => 0,
                'fat_g' => 0,
                'fibre_total_g' => 0,
                'free_sugar_g' => 0,
                'sfa_g' => 0,
                'mufa_g' => 0,
                'pufa_g' => 0,
                'calcium_mg' => 0,
                'iron_mg' => 0,
                'zinc_mg' => 0,
                'magnesium_mg' => 0,
                'vit_c_mg' => 0,
                'vit_d_ug' => 0,
                'b1_mg' => 0,
                'b2_mg' => 0,
                'b3_mg' => 0,
                'b6_mg' => 0,
                'folate_ug' => 0,
                'b12_ug' => 0,
            ];

            $items = [];

            if ($meal->items) {
                foreach ($meal->items as $item) {
                    $food = $item->food;
                    $nutrients = $food->nutrients;

                    $foodItem = [
                        'food_id' => $food->id,
                        'food_name' => $food->food_name,
                        'regional_name' => $food->regional_name,
                        'portion_g' => $item->portion_size ?? ($food->serving_g ?? 150),
                        'nutrients' => [
                            'energy_kcal' => $nutrients->energy_kcal ?? 0,
                            'protein_g' => $nutrients->protein_g ?? 0,
                            'carb_g' => $nutrients->carb_g ?? 0,
                            'fat_g' => $nutrients->fat_g ?? 0,
                            'fibre_total_g' => $nutrients->fibre_total_g ?? 0,
                            'free_sugar_g' => $nutrients->free_sugar_g ?? 0,
                            'sfa_g' => $nutrients->sfa_g ?? 0,
                            'mufa_g' => $nutrients->mufa_g ?? 0,
                            'pufa_g' => $nutrients->pufa_g ?? 0,
                            'calcium_mg' => $nutrients->calcium_mg ?? 0,
                            'iron_mg' => $nutrients->iron_mg ?? 0,
                            'zinc_mg' => $nutrients->zinc_mg ?? 0,
                            'magnesium_mg' => $nutrients->magnesium_mg ?? 0,
                            'vit_c_mg' => $nutrients->vit_c_mg ?? 0,
                            'vit_d_ug' => $nutrients->vit_d_ug ?? 0,
                            'b1_mg' => $nutrients->b1_mg ?? 0,
                            'b2_mg' => $nutrients->b2_mg ?? 0,
                            'b3_mg' => $nutrients->b3_mg ?? 0,
                            'b6_mg' => $nutrients->b6_mg ?? 0,
                            'folate_ug' => $nutrients->folate_ug ?? 0,
                            'b12_ug' => $nutrients->b12_ug ?? 0,
                        ],
                        'pairing_notes' => $this->generatePairingNotes($food, $assessment),
                    ];

                    foreach ($foodItem['nutrients'] as $key => $value) {
                        if (isset($totalNutrients[$key])) {
                            $totalNutrients[$key] += $value;
                        }
                    }

                    $items[] = $foodItem;
                }
            }

            $planData = $dietPlan->plan_data;
            if (is_string($planData)) {
                $planData = json_decode($planData, true) ?? [];
            }
            if (!is_array($planData)) {
                $planData = [];
            }

            $alternatives = [];
            if (isset($planData['generation_debug']['meals'])) {
                foreach ($planData['generation_debug']['meals'] as $dm) {
                    if ($dm['day'] === $currentDay && $dm['slot'] === $meal->meal_slot) {
                        foreach ($dm['candidate_scores'] as $candidate) {
                            $altFood = \App\Models\Nutrition\FoodMaster::find($candidate['food_id']);
                            if ($altFood) {
                                $alternatives[] = [
                                    'food_id' => $altFood->id,
                                    'food_name' => $altFood->food_name,
                                    'energy_kcal' => round($altFood->nutrients->energy_kcal ?? 0),
                                    'protein_g' => round($altFood->nutrients->protein_g ?? 0, 1),
                                    'score' => round($candidate['score'], 1)
                                ];
                            }
                        }
                        break;
                    }
                }
            }

            $mealsByDay[$currentDay][$meal->meal_slot] = [
                'items' => $items,
                'total_nutrients' => $totalNutrients,
                'focus' => $meal->focus,
                'meal_id' => $meal->id,
                'alternatives' => $alternatives
            ];
        }

        foreach ($mealsByDay as &$dayMeals) {
            uksort($dayMeals, fn ($a, $b) => ($slotOrder[$a] ?? 99) <=> ($slotOrder[$b] ?? 99));
        }

        return $mealsByDay;
    }

    protected function generatePairingNotes($food, ChildAssessment $assessment): array
    {
        $notes = [];
        $nutrients = $food->nutrients;

        if (($nutrients->iron_mg ?? 0) > 2) {
            $notes[] = 'Rich in iron. Serve with lemon juice or amla (Vitamin C) to enhance absorption by up to 3x';
        }

        if (($nutrients->calcium_mg ?? 0) > 200) {
            $notes[] = 'Excellent calcium source. Keep separate from iron-rich foods by 2+ hours for better absorption';
        }

        if (($nutrients->zinc_mg ?? 0) > 1) {
            $notes[] = 'Good zinc content. Pair with vitamin A/C rich vegetables for better absorption';
        }

        return $notes;
    }

    public function getClinicalTips(ChildAssessment $assessment, array $gaps): array
    {
        $tips = [];

        $comboEngine = app('App\Services\Nutrition\ComboEngine');
        $flags = $assessment->clinicalFlags()->pluck('flag_name')->toArray();

        $dbTips = $comboEngine->getCombos($assessment->main_goal ?? 'General Growth', $flags);

        foreach ($dbTips as $tip) {
            $tips[] = [
                'benefit' => $tip->benefit ?? 'Nutrition Tip',
                'combination' => $tip->combination ?? '',
                'science_reason' => $tip->science_reason ?? '',
            ];
        }

        foreach ($gaps as $nutrient => $gap) {
            if (isset($gap['gap_category']) && $gap['gap_category'] === 'Critical gap') {
                if ($nutrient === 'iron_mg') {
                    $tips[] = [
                        'benefit' => 'Iron & Anemia Prevention',
                        'combination' => 'Iron-rich foods + Vitamin C source (lemon, amla, orange)',
                        'science_reason' => 'Vitamin C increases non-heme iron absorption from ~20% to ~60%, effectively tripling bioavailability',
                    ];
                } elseif ($nutrient === 'calcium_mg') {
                    $tips[] = [
                        'benefit' => 'Bone & Teeth Development',
                        'combination' => 'Calcium sources + Vitamin D exposure (15 min sun daily)',
                        'science_reason' => 'Vitamin D activates calcium-binding proteins in intestines, increasing absorption from 30% to 65%',
                    ];
                } elseif ($nutrient === 'zinc_mg') {
                    $tips[] = [
                        'benefit' => 'Immune Function & Growth',
                        'combination' => 'Zinc-rich foods + vitamin A/C vegetables',
                        'science_reason' => 'Zinc absorption is enhanced by vitamin A and inhibited by phytates; pair with vitamin-rich vegetables',
                    ];
                }
            }
        }

        return $tips;
    }

    public function getUserPurchasedPlansCount(Child $child): int
    {
        return (int) DietSubscription::query()
            ->where('user_id', Auth::id())
            ->where('child_id', $child->id)
            ->where('status', 'active')
            ->where('payment_status', 'paid')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->sum('plans_remaining');
    }

    public function decrementPurchasedPlans(Child $child): ?int
    {
        $subscriptionId = DietSubscription::query()
            ->where('user_id', Auth::id())
            ->where('child_id', $child->id)
            ->where('status', 'active')
            ->where('payment_status', 'paid')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where('plans_remaining', '>', 0)
            ->orderBy('expires_at')
            ->value('id');

        if (!$subscriptionId) {
            return null;
        }

        $updated = DietSubscription::query()
            ->whereKey($subscriptionId)
            ->where('plans_remaining', '>', 0)
            ->decrement('plans_remaining');

        return $updated === 1 ? (int) $subscriptionId : null;
    }

    public function restorePurchasedPlan(int $subscriptionId): void
    {
        DietSubscription::query()
            ->whereKey($subscriptionId)
            ->increment('plans_remaining');
    }

    public function authorizeChildAccess(Child $child): void
    {
        if ($child->user_id !== Auth::id()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Unauthorized access to child profile');
        }
    }

}
