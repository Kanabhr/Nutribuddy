<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Nutrition\DeficiencyRule;

/**
 * DeficiencyRulesSeeder
 *
 * Seeds deficiency_rules from the Deficiency Signs CSV.
 *
 * ──────────────────────────────────────────────────────────────────
 * Q26 → CSV mapping  (NEEDS NUTRITIONIST REVIEW — see docs/seeder-notes.md)
 * ──────────────────────────────────────────────────────────────────
 * Q26 option text (exact)          │ Closest CSV "What you notice" row
 * ─────────────────────────────────┼────────────────────────────────────────
 * Pale eyes/skin                   │ "Pale inner eyelids/skin, tired, breathless on play"
 * Tiredness                        │ "Pale inner eyelids/skin, tired, breathless on play"
 *                                  │   (same row — tiredness is co-listed)
 * Hair fall                        │ "Hair fall, thin brittle nails"
 * Brittle nails                    │ "Hair fall, thin brittle nails"
 *                                  │   (same row — brittle nails is co-listed)
 * Mouth-corner cracks/ulcers       │ "Cracks at mouth corners, sore tongue, ulcers"
 * Night-vision trouble             │ "Night-vision trouble, dry eyes"
 * Delayed teeth/bowed legs         │ "Bowed legs, delayed teeth/walking, bone or leg pain"
 * Poor focus                       │ "Poor concentration, irritable, low energy at school"
 * None                             │ — (no rule; option skipped)
 *
 * CSV rows with NO Q26 match (skipped, listed in docs/seeder-notes.md):
 *   "Falls ill often, cuts heal slowly"          → Zinc / Vitamin C
 *   "White spots on nails, poor appetite..."     → Zinc
 *   "Hard/infrequent stools, tummy pain"         → Fibre + water
 *   "Excess belly fat, breathless, low activity" → Energy imbalance
 *
 * ──────────────────────────────────────────────────────────────────
 * deficiency_foods
 * ──────────────────────────────────────────────────────────────────
 * The deficiency_foods table has a FK to food_master (legacy table).
 * food_master rows are populated from an imported SQL file that is
 * outside this seeder's scope.  deficiency_foods rows are NOT seeded
 * here to avoid FK violations; they must be linked after the SQL
 * import runs. The food names from the CSV are stored in the
 * `smart_combination` field of deficiency_rules as a fallback.
 *
 * Uses updateOrCreate on (sign) so it is re-runnable without duplicates.
 */
class DeficiencyRulesSeeder extends Seeder
{
    public function run(): void
    {
        $rules = $this->rules();

        foreach ($rules as $rule) {
            DeficiencyRule::updateOrCreate(
                ['sign' => $rule['sign']],
                [
                    'likely_gap'        => $rule['likely_gap'],
                    'confirmation_test' => $rule['confirmation_test'],
                    'smart_combination' => $rule['smart_combination'],
                    'common_group'      => $rule['common_group'],
                ]
            );
        }

        $this->command->info('DeficiencyRulesSeeder: ' . count($rules) . ' rules upserted into deficiency_rules.');
        $this->command->warn('deficiency_foods rows NOT seeded — requires food_master SQL import first (see docs/seeder-notes.md).');
    }

    /**
     * Each entry uses the EXACT Q26 option text as 'sign'
     * (RiskFlaggingEngine does an exact whereIn on this column).
     *
     * Likely gap, confirmation test, foods, and common group
     * are taken verbatim from the CSV source rows — no values invented.
     */
    private function rules(): array
    {
        return [
            // Q26: "Pale eyes/skin"
            // CSV row: "Pale inner eyelids/skin, tired, breathless on play"
            [
                'sign'              => 'Pale eyes/skin',
                'likely_gap'        => 'Iron (anemia)',
                'confirmation_test' => 'Hb, Ferritin',
                'smart_combination' => 'Dal, spinach/greens, rajma, dates, jaggery, poha, (egg/liver if non-veg) — always add a Vitamin-C food (lemon/amla/guava) to the iron meal',
                'common_group'      => 'Teen girls, veg kids, toddlers',
            ],

            // Q26: "Tiredness"
            // CSV row: "Pale inner eyelids/skin, tired, breathless on play"
            // Also overlaps with "Poor concentration, irritable, low energy at school" (Iron/Omega-3)
            // Using the iron-anemia row as the primary mapping; poor focus gets its own entry.
            [
                'sign'              => 'Tiredness',
                'likely_gap'        => 'Iron (anemia)',
                'confirmation_test' => 'Hb, Ferritin',
                'smart_combination' => 'Dal, spinach/greens, rajma, dates, jaggery, poha, (egg/liver if non-veg) — always add a Vitamin-C food (lemon/amla/guava) to the iron meal',
                'common_group'      => 'Teen girls, veg kids, toddlers',
            ],

            // Q26: "Hair fall"
            // CSV row: "Hair fall, thin brittle nails"
            [
                'sign'              => 'Hair fall',
                'likely_gap'        => 'Protein / Iron',
                'confirmation_test' => 'Hb, total protein',
                'smart_combination' => 'Eggs, paneer, dal, milk, nuts, chana — cereal+pulse together for complete protein',
                'common_group'      => 'Picky eaters, veg kids',
            ],

            // Q26: "Brittle nails"
            // CSV row: "Hair fall, thin brittle nails"
            [
                'sign'              => 'Brittle nails',
                'likely_gap'        => 'Protein / Iron',
                'confirmation_test' => 'Hb, total protein',
                'smart_combination' => 'Eggs, paneer, dal, milk, nuts, chana — cereal+pulse together for complete protein',
                'common_group'      => 'Picky eaters, veg kids',
            ],

            // Q26: "Mouth-corner cracks/ulcers"
            // CSV row: "Cracks at mouth corners, sore tongue, ulcers"
            [
                'sign'              => 'Mouth-corner cracks/ulcers',
                'likely_gap'        => 'B-complex (B2/B3/B12)',
                'confirmation_test' => 'B12, folate',
                'smart_combination' => 'Milk, curd, egg, whole grains; B12 focus for veg — milk + whole grain; egg at breakfast',
                'common_group'      => 'Strict-veg kids (B12)',
            ],

            // Q26: "Night-vision trouble"
            // CSV row: "Night-vision trouble, dry eyes"
            [
                'sign'              => 'Night-vision trouble',
                'likely_gap'        => 'Vitamin A',
                'confirmation_test' => 'Serum retinol',
                'smart_combination' => 'Carrot, pumpkin, mango, spinach, papaya — cook with a little ghee/oil (A is fat-soluble)',
                'common_group'      => 'Low fruit/veg diets',
            ],

            // Q26: "Delayed teeth/bowed legs"
            // CSV row: "Bowed legs, delayed teeth/walking, bone or leg pain"
            [
                'sign'              => 'Delayed teeth/bowed legs',
                'likely_gap'        => 'Vitamin D (rickets)',
                'confirmation_test' => '25-OH Vitamin D',
                'smart_combination' => 'Sunlight 20-30 min, fortified milk, egg yolk, fish; supplement usually needed — Vit D food + midday sun; pair with calcium',
                'common_group'      => 'Almost all Indian kids at risk',
            ],

            // Q26: "Poor focus"
            // CSV row: "Poor concentration, irritable, low energy at school"
            [
                'sign'              => 'Poor focus',
                'likely_gap'        => 'Iron / Omega-3 / skipped breakfast',
                'confirmation_test' => 'Hb',
                'smart_combination' => 'Walnuts, flax/chia, egg, protein breakfast, banana — protein + fruit breakfast, never skipped',
                'common_group'      => 'School-age, exam time',
            ],

            // Q26 option "None" → no rule (no signs = no flags; intentionally not seeded)
        ];
    }
}
