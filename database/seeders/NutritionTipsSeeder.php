<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Nutrition\NutritionTip;

/**
 * NutritionTipsSeeder
 *
 * Seeds nutrition_tips from the Tips & Combos CSV (35 rows).
 *
 * ──────────────────────────────────────────────────────────────────
 * Age-group → age_min / age_max mapping
 * ──────────────────────────────────────────────────────────────────
 *   "All"    → age_min=null, age_max=null
 *   "2-5"    → age_min=2,    age_max=5
 *   "6-9"    → age_min=6,    age_max=9
 *   "10-12"  → age_min=10,   age_max=12
 *   "10-15"  → age_min=10,   age_max=15
 *   "13-15"  → age_min=13,   age_max=15
 *
 * ──────────────────────────────────────────────────────────────────
 * Sex mapping
 * ──────────────────────────────────────────────────────────────────
 *   "Both"  → 'all'
 *   "Boys"  → 'boy'
 *   "Girls" → 'girl'
 *
 * ──────────────────────────────────────────────────────────────────
 * Benefit stored in nutrition_tips
 * ──────────────────────────────────────────────────────────────────
 * The 'benefit' column stores the CSV sheet's category name exactly
 * as-is so rows are human-readable.  ComboEngine translates the
 * questionnaire goal labels to one or more category names via the
 * GOAL_CATEGORY_MAP constant defined in ComboEngine (see that file).
 *
 * Uses updateOrCreate on (benefit, age_min, age_max, sex, combination)
 * so the seeder is safe to re-run.
 */
class NutritionTipsSeeder extends Seeder
{
    public function run(): void
    {
        $tips = $this->tips();

        foreach ($tips as $tip) {
            NutritionTip::updateOrCreate(
                [
                    'benefit'     => $tip['benefit'],
                    'age_min'     => $tip['age_min'],
                    'age_max'     => $tip['age_max'],
                    'sex'         => $tip['sex'],
                    'combination' => $tip['combination'],
                ],
                [
                    'science_reason' => $tip['science_reason'],
                ]
            );
        }

        $this->command->info('NutritionTipsSeeder: ' . count($tips) . ' tips upserted into nutrition_tips.');
    }

    /**
     * All 35 tips from the Tips & Combos CSV.
     * Values are taken verbatim from the CSV — nothing invented.
     * 'key_nutrient' from the CSV is folded into science_reason for traceability.
     */
    private function tips(): array
    {
        return [
            // ── Immunity ──────────────────────────────────────────────────────
            [
                'benefit'        => 'Immunity',
                'age_min'        => null,
                'age_max'        => null,
                'sex'            => 'all',
                'combination'    => 'Vitamin-C fruit — guava, orange, amla or a squeeze of lemon on breakfast',
                'science_reason' => 'Vitamin C supports white-blood-cell function; morning dose starts the day topped up. Key nutrient: Vit C',
            ],
            [
                'benefit'        => 'Immunity',
                'age_min'        => null,
                'age_max'        => null,
                'sex'            => 'all',
                'combination'    => 'Warm milk with a pinch of turmeric (haldi doodh) at bedtime',
                'science_reason' => 'Turmeric\'s curcumin is anti-inflammatory; warm milk aids restful sleep and recovery. Key nutrient: Curcumin, Ca',
            ],
            [
                'benefit'        => 'Immunity',
                'age_min'        => 2,
                'age_max'        => 5,
                'sex'            => 'all',
                'combination'    => 'Curd / dahi bowl (add fruit) mid-morning',
                'science_reason' => 'Probiotic gut bacteria train a young child\'s immune system; ~70% of immunity is gut-based. Key nutrient: Probiotics',
            ],
            [
                'benefit'        => 'Immunity',
                'age_min'        => 6,
                'age_max'        => 9,
                'sex'            => 'all',
                'combination'    => 'Roasted pumpkin seeds or a few cashews in the evening',
                'science_reason' => 'Zinc is essential for immune-cell production; seeds are a rich, kid-friendly source. Key nutrient: Zinc',
            ],
            [
                'benefit'        => 'Immunity',
                'age_min'        => 10,
                'age_max'        => 12,
                'sex'            => 'all',
                'combination'    => 'Turmeric always with a pinch of black pepper',
                'science_reason' => 'Piperine in pepper multiplies curcumin absorption many times over. Key nutrient: Curcumin',
            ],

            // ── Brain & Focus ─────────────────────────────────────────────────
            [
                'benefit'        => 'Brain & Focus',
                'age_min'        => null,
                'age_max'        => null,
                'sex'            => 'all',
                'combination'    => '4-5 soaked almonds + 1 walnut with breakfast',
                'science_reason' => 'Walnut omega-3 (ALA) and almond vitamin E support memory and concentration for school. Key nutrient: Omega-3, Vit E',
            ],
            [
                'benefit'        => 'Brain & Focus',
                'age_min'        => 2,
                'age_max'        => 5,
                'sex'            => 'all',
                'combination'    => 'Ghee on dal-rice / khichdi; mashed nut powder in milk',
                'science_reason' => 'Healthy fats build the brain\'s myelin in early years; nut powder is safe for toddlers. Key nutrient: Healthy fat',
            ],
            [
                'benefit'        => 'Brain & Focus',
                'age_min'        => 6,
                'age_max'        => 9,
                'sex'            => 'all',
                'combination'    => 'Flax or chia seeds blended into a banana milkshake in the evening',
                'science_reason' => 'Plant omega-3 plus slow carbs give steady focus without a sugar spike. Key nutrient: Omega-3',
            ],
            [
                'benefit'        => 'Brain & Focus',
                'age_min'        => 10,
                'age_max'        => 15,
                'sex'            => 'all',
                'combination'    => 'Egg or paneer at breakfast (protein-first)',
                'science_reason' => 'Protein gives amino acids for neurotransmitters; a protein breakfast steadies attention. Key nutrient: Protein, Choline',
            ],

            // ── Strength & Muscle ─────────────────────────────────────────────
            [
                'benefit'        => 'Strength & Muscle',
                'age_min'        => null,
                'age_max'        => null,
                'sex'            => 'all',
                'combination'    => 'Cereal + pulse together at lunch — dal-rice, khichdi, idli, roti-dal, rajma-rice',
                'science_reason' => 'Grains and pulses complete each other\'s amino acids into full protein. Key nutrient: Complete protein',
            ],
            [
                'benefit'        => 'Strength & Muscle',
                'age_min'        => 10,
                'age_max'        => 12,
                'sex'            => 'boy',
                'combination'    => 'Sprouts chaat or chana chaat + a glass of milk in the evening',
                'science_reason' => 'Growth-spurt years need extra protein; sprouts + dairy stack plant and dairy protein. Key nutrient: Protein',
            ],
            [
                'benefit'        => 'Strength & Muscle',
                'age_min'        => 13,
                'age_max'        => 15,
                'sex'            => 'boy',
                'combination'    => 'Larger portions: paneer, egg, chicken, peanut chikki between meals',
                'science_reason' => 'Boys 13-15 need ~2860 kcal and ~45 g protein/day — the highest of any child band. Key nutrient: Energy, Protein',
            ],
            [
                'benefit'        => 'Strength & Muscle',
                'age_min'        => 13,
                'age_max'        => 15,
                'sex'            => 'girl',
                'combination'    => 'Paneer / dal + a whole-grain roti at lunch; don\'t skip meals',
                'science_reason' => 'Teen girls\' protein need (~43 g) stays high; skipping meals stalls growth and energy. Key nutrient: Protein',
            ],

            // ── Bones & Calcium ───────────────────────────────────────────────
            [
                'benefit'        => 'Bones & Calcium',
                'age_min'        => null,
                'age_max'        => null,
                'sex'            => 'all',
                'combination'    => 'A glass of milk at bedtime',
                'science_reason' => 'Calcium is best absorbed when the day\'s iron-rich meals are done; supports bone growth. Key nutrient: Calcium',
            ],
            [
                'benefit'        => 'Bones & Calcium',
                'age_min'        => 2,
                'age_max'        => 5,
                'sex'            => 'all',
                'combination'    => 'Ragi porridge / ragi malt',
                'science_reason' => 'Ragi (finger millet) is one of the richest plant calcium sources — ideal for little ones. Key nutrient: Calcium',
            ],
            [
                'benefit'        => 'Bones & Calcium',
                'age_min'        => null,
                'age_max'        => null,
                'sex'            => 'all',
                'combination'    => '10-15 min sunlight + calcium food in the morning',
                'science_reason' => 'Vitamin D from sunlight is required to actually absorb calcium; pair the two. Key nutrient: Vit D + Ca',
            ],
            [
                'benefit'        => 'Bones & Calcium',
                'age_min'        => 6,
                'age_max'        => 9,
                'sex'            => 'all',
                'combination'    => 'Til (sesame) chikki or til ladoo in the evening',
                'science_reason' => 'Sesame is a calcium powerhouse; jaggery adds iron — a smart Indian bone snack. Key nutrient: Calcium',
            ],
            [
                'benefit'        => 'Bones & Calcium',
                'age_min'        => 10,
                'age_max'        => 15,
                'sex'            => 'girl',
                'combination'    => 'Paneer, curd, ragi daily — build peak bone mass now',
                'science_reason' => 'Bone mass banked in the teen years protects lifelong; girls\' needs rise to 800-1000 mg. Key nutrient: Calcium',
            ],

            // ── Iron & Blood ──────────────────────────────────────────────────
            [
                'benefit'        => 'Iron & Blood',
                'age_min'        => null,
                'age_max'        => null,
                'sex'            => 'all',
                'combination'    => 'Iron food (dal, spinach, rajma) + a Vitamin-C food (lemon, tomato, amla) at lunch',
                'science_reason' => 'Vitamin C converts plant iron into an absorbable form — can triple absorption. Key nutrient: Iron + Vit C',
            ],
            [
                'benefit'        => 'Iron & Blood',
                'age_min'        => 10,
                'age_max'        => 12,
                'sex'            => 'girl',
                'combination'    => 'Dates, jaggery-peanut chikki, pumpkin seeds daily',
                'science_reason' => 'Girls\' iron need jumps to ~28 mg with puberty; small daily iron snacks add up. Key nutrient: Iron',
            ],
            [
                'benefit'        => 'Iron & Blood',
                'age_min'        => 13,
                'age_max'        => 15,
                'sex'            => 'girl',
                'combination'    => 'Keep milk/tea away from the iron meal by ~1 hour',
                'science_reason' => 'Calcium and tea tannins block iron; separating them protects a teen girl\'s iron status. Key nutrient: Iron',
            ],
            [
                'benefit'        => 'Iron & Blood',
                'age_min'        => 6,
                'age_max'        => 9,
                'sex'            => 'all',
                'combination'    => 'Poha (naturally iron-rich) with lemon in the morning',
                'science_reason' => 'Flattened rice carries iron; lemon\'s vitamin C unlocks it — a classic breakfast combo. Key nutrient: Iron + Vit C',
            ],

            // ── Energy & Growth ───────────────────────────────────────────────
            [
                'benefit'        => 'Energy & Growth',
                'age_min'        => 2,
                'age_max'        => 5,
                'sex'            => 'all',
                'combination'    => 'Small frequent meals — 3 meals + 2 snacks',
                'science_reason' => 'Little stomachs can\'t hold much; frequent nutrient-dense snacks keep energy and growth up. Key nutrient: Energy',
            ],
            [
                'benefit'        => 'Energy & Growth',
                'age_min'        => null,
                'age_max'        => null,
                'sex'            => 'all',
                'combination'    => 'Never skip breakfast — include a protein + a fruit',
                'science_reason' => 'Breakfast refuels after the overnight fast and sets blood sugar for the school morning. Key nutrient: Energy, Protein',
            ],
            [
                'benefit'        => 'Energy & Growth',
                'age_min'        => 13,
                'age_max'        => 15,
                'sex'            => 'boy',
                'combination'    => 'Banana milkshake, dry-fruit ladoo, peanut chikki in the evening',
                'science_reason' => 'Calorie-dense whole-food snacks meet a teen boy\'s high energy need cleanly. Key nutrient: Energy',
            ],

            // ── Digestion & Gut ───────────────────────────────────────────────
            [
                'benefit'        => 'Digestion & Gut',
                'age_min'        => null,
                'age_max'        => null,
                'sex'            => 'all',
                'combination'    => 'Curd / buttermilk with meals',
                'science_reason' => 'Probiotics and the cooling effect aid digestion and nutrient uptake. Key nutrient: Probiotics',
            ],
            [
                'benefit'        => 'Digestion & Gut',
                'age_min'        => 2,
                'age_max'        => 5,
                'sex'            => 'all',
                'combination'    => 'Soak almonds overnight, peel before giving',
                'science_reason' => 'Soaking removes skin tannins and softens nuts — gentler on a toddler\'s gut. Key nutrient: Fibre',
            ],
            [
                'benefit'        => 'Digestion & Gut',
                'age_min'        => 6,
                'age_max'        => 9,
                'sex'            => 'all',
                'combination'    => 'Sprouted moong / chana',
                'science_reason' => 'Sprouting cuts anti-nutrients and boosts vitamin C and easy-to-digest protein. Key nutrient: Fibre, Vit C',
            ],
            [
                'benefit'        => 'Digestion & Gut',
                'age_min'        => null,
                'age_max'        => null,
                'sex'            => 'all',
                'combination'    => 'Whole fruit over juice; keep the fibre',
                'science_reason' => 'Fibre feeds gut bacteria and slows sugar; juice loses it and spikes blood sugar. Key nutrient: Fibre',
            ],

            // ── Eyes & Vision ─────────────────────────────────────────────────
            [
                'benefit'        => 'Eyes & Vision',
                'age_min'        => null,
                'age_max'        => null,
                'sex'            => 'all',
                'combination'    => 'Carrot / pumpkin / spinach cooked with a little ghee or oil at lunch',
                'science_reason' => 'Vitamin A is fat-soluble — a little fat is needed to absorb it for eye health. Key nutrient: Vit A + fat',
            ],
            [
                'benefit'        => 'Eyes & Vision',
                'age_min'        => 2,
                'age_max'        => 5,
                'sex'            => 'all',
                'combination'    => 'Mango (in season), papaya, orange',
                'science_reason' => 'Beta-carotene fruits build vitamin A for developing vision. Key nutrient: Vit A',
            ],

            // ── Healthy Weight ────────────────────────────────────────────────
            [
                'benefit'        => 'Healthy Weight',
                'age_min'        => null,
                'age_max'        => null,
                'sex'            => 'all',
                'combination'    => 'Choose fruit + nuts or a light snack over fried/packaged in the evening',
                'science_reason' => 'Whole-food snacks give nutrients with steady energy, not empty calories. Key nutrient: Fibre, Protein',
            ],
            [
                'benefit'        => 'Healthy Weight',
                'age_min'        => 10,
                'age_max'        => 15,
                'sex'            => 'all',
                'combination'    => 'Fill half the plate with vegetables',
                'science_reason' => 'Veg add volume, fibre and micronutrients with few calories — natural portion control. Key nutrient: Fibre',
            ],

            // ── Overall ───────────────────────────────────────────────────────
            [
                'benefit'        => 'Overall',
                'age_min'        => null,
                'age_max'        => null,
                'sex'            => 'all',
                'combination'    => 'Eat a rainbow — different coloured fruits & veg across the week',
                'science_reason' => 'Each colour signals different vitamins/antioxidants; variety covers more micronutrients. Key nutrient: Antioxidants',
            ],
            [
                'benefit'        => 'Overall',
                'age_min'        => null,
                'age_max'        => null,
                'sex'            => 'all',
                'combination'    => 'Hydration: water through the day, limit sugary drinks',
                'science_reason' => 'Water supports every body process; sugary drinks crowd out nutrient-dense foods. Key nutrient: Water',
            ],
        ];
    }
}
