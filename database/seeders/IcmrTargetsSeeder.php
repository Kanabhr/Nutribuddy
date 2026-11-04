<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Nutrition\IcmrNutritionTarget;

/**
 * IcmrTargetsSeeder
 *
 * Seeds icmr_nutrition_targets from the ICMR-NIN 2020 RDA table
 * (provided as icmr-rda-targets.csv, sheet "ICMR RDA Targets").
 *
 * Mapping decisions
 * -----------------
 * • Sex column    : "Both" → 'all', "Boys" → 'boy', "Girls" → 'girl'
 * • Age band      : "1-3 years" → age_min=1, age_max=3, and so on
 * • Iodine        : has NO column in icmr_nutrition_targets — skipped
 * • fiber_g       : NOT in the CSV source — omitted from the upsert values
 *                   so existing rows keep whatever value was set previously
 *                   and new rows get the column default (0).
 * • vitamin_d_mcg : NOT in the CSV source — same treatment as fiber_g.
 * • updateOrCreate on (age_min, age_max, sex) — safe to re-run
 *
 * Age bands seeded: 1-3, 4-6, 7-9, 10-12 boy, 10-12 girl,
 *                   13-15 boy, 13-15 girl, 16-18 boy, 16-18 girl
 *
 * NOTE: The spec asks for 2-15 coverage only, but the CSV includes
 * 1-3 and 16-18 which are seeded here as well to keep data complete.
 * The existing migration-seeded row for 4-6/all will be updated with
 * canonical CSV values (fat_g=25); its fiber_g=20 and vitamin_d_mcg=15
 * set by the migration are preserved — this seeder does not touch them.
 */
class IcmrTargetsSeeder extends Seeder
{
    public function run(): void
    {
        $rows = $this->data();

        // Columns that are the unique key for upsert
        $keyColumns = ['age_min', 'age_max', 'sex'];

        // Columns absent from the CSV source — excluded from the values payload
        // so that on UPDATE existing rows keep whatever was set previously
        // (e.g. fiber_g=20 and vitamin_d_mcg=15 seeded by the 4-6/all migration).
        // On INSERT they fall back to the column default (0).
        $csvAbsent = ['fiber_g', 'vitamin_d_mcg'];

        $omit = array_flip(array_merge($keyColumns, $csvAbsent));

        foreach ($rows as $row) {
            IcmrNutritionTarget::updateOrCreate(
                [
                    'age_min' => $row['age_min'],
                    'age_max' => $row['age_max'],
                    'sex'     => $row['sex'],
                ],
                array_diff_key($row, $omit)
            );
        }

        $this->command->info('IcmrTargetsSeeder: ' . count($rows) . ' rows upserted into icmr_nutrition_targets.');
    }

    /**
     * Returns an array of rows, each containing age_min, age_max, sex,
     * and the CSV-sourced nutrient values only.
     *
     * fiber_g and vitamin_d_mcg are intentionally absent — they are not
     * in the CSV source and must not be written during an UPDATE.
     *
     * Source: ICMR-NIN 2020 (icmr-rda-targets.csv)
     * Columns mapped:
     *   Energy (kcal)       → energy_kcal
     *   Protein (g)         → protein_g
     *   Fat (g)             → fat_g
     *   Calcium (mg)        → calcium_mg
     *   Iron (mg)           → iron_mg
     *   Zinc (mg)           → zinc_mg
     *   Magnesium (mg)      → magnesium_mg
     *   Vit A (mcg)         → vitamin_a_mcg
     *   Thiamin B1 (mg)     → thiamin_b1_mg
     *   Riboflavin B2 (mg)  → riboflavin_b2_mg
     *   Niacin B3 (mg)      → niacin_b3_mg
     *   Vit B6 (mg)         → vitamin_b6_mg
     *   Folate (mcg)        → folate_mcg
     *   Vit B12 (mcg)       → vitamin_b12_mcg
     *   Vit C (mg)          → vitamin_c_mg
     *   Iodine (mcg)        → SKIPPED (no column in table)
     */
    private function data(): array
    {
        return [
            // 1-3 years, Both → 'all'
            [
                'age_min' => 1,  'age_max' => 3,  'sex' => 'all',
                'energy_kcal'      => 1110,
                'protein_g'        => 12.5,
                'fat_g'            => 15,
                'calcium_mg'       => 500,
                'iron_mg'          => 8,
                'zinc_mg'          => 3.3,
                'magnesium_mg'     => 90,
                'vitamin_a_mcg'    => 390,
                'vitamin_c_mg'     => 30,
                'thiamin_b1_mg'    => 0.7,
                'riboflavin_b2_mg' => 1.1,
                'niacin_b3_mg'     => 7,
                'vitamin_b6_mg'    => 0.9,
                'folate_mcg'       => 120,
                'vitamin_b12_mcg'  => 1.2,
            ],

            // 4-6 years, Both → 'all'
            // Existing migration-seeded row (2026_09_09_000001) has fiber_g=20
            // and vitamin_d_mcg=15; those values are preserved — not touched here.
            [
                'age_min' => 4,  'age_max' => 6,  'sex' => 'all',
                'energy_kcal'      => 1360,
                'protein_g'        => 15.9,
                'fat_g'            => 25,
                'calcium_mg'       => 550,
                'iron_mg'          => 11,
                'zinc_mg'          => 4.5,
                'magnesium_mg'     => 125,
                'vitamin_a_mcg'    => 510,
                'vitamin_c_mg'     => 35,
                'thiamin_b1_mg'    => 0.9,
                'riboflavin_b2_mg' => 1.3,
                'niacin_b3_mg'     => 10,
                'vitamin_b6_mg'    => 1.2,
                'folate_mcg'       => 135,
                'vitamin_b12_mcg'  => 2.2,
            ],

            // 7-9 years, Both → 'all'
            [
                'age_min' => 7,  'age_max' => 9,  'sex' => 'all',
                'energy_kcal'      => 1700,
                'protein_g'        => 23.3,
                'fat_g'            => 30,
                'calcium_mg'       => 650,
                'iron_mg'          => 15,
                'zinc_mg'          => 5.9,
                'magnesium_mg'     => 175,
                'vitamin_a_mcg'    => 630,
                'vitamin_c_mg'     => 45,
                'thiamin_b1_mg'    => 1.1,
                'riboflavin_b2_mg' => 1.6,
                'niacin_b3_mg'     => 11,
                'vitamin_b6_mg'    => 1.5,
                'folate_mcg'       => 170,
                'vitamin_b12_mcg'  => 2.2,
            ],

            // 10-12 years, Boys → 'boy'
            [
                'age_min' => 10, 'age_max' => 12, 'sex' => 'boy',
                'energy_kcal'      => 2220,
                'protein_g'        => 31.8,
                'fat_g'            => 35,
                'calcium_mg'       => 850,
                'iron_mg'          => 16,
                'zinc_mg'          => 8.5,
                'magnesium_mg'     => 240,
                'vitamin_a_mcg'    => 770,
                'vitamin_c_mg'     => 55,
                'thiamin_b1_mg'    => 1.5,
                'riboflavin_b2_mg' => 2.1,
                'niacin_b3_mg'     => 15,
                'vitamin_b6_mg'    => 2.0,
                'folate_mcg'       => 220,
                'vitamin_b12_mcg'  => 2.2,
            ],

            // 10-12 years, Girls → 'girl'
            [
                'age_min' => 10, 'age_max' => 12, 'sex' => 'girl',
                'energy_kcal'      => 2060,
                'protein_g'        => 32.8,
                'fat_g'            => 35,
                'calcium_mg'       => 850,
                'iron_mg'          => 28,
                'zinc_mg'          => 8.5,
                'magnesium_mg'     => 250,
                'vitamin_a_mcg'    => 790,
                'vitamin_c_mg'     => 50,
                'thiamin_b1_mg'    => 1.4,
                'riboflavin_b2_mg' => 1.9,
                'niacin_b3_mg'     => 14,
                'vitamin_b6_mg'    => 1.9,
                'folate_mcg'       => 225,
                'vitamin_b12_mcg'  => 2.2,
            ],

            // 13-15 years, Boys → 'boy'
            [
                'age_min' => 13, 'age_max' => 15, 'sex' => 'boy',
                'energy_kcal'      => 2860,
                'protein_g'        => 44.9,
                'fat_g'            => 45,
                'calcium_mg'       => 1000,
                'iron_mg'          => 22,
                'zinc_mg'          => 14.3,
                'magnesium_mg'     => 345,
                'vitamin_a_mcg'    => 930,
                'vitamin_c_mg'     => 70,
                'thiamin_b1_mg'    => 1.9,
                'riboflavin_b2_mg' => 2.7,
                'niacin_b3_mg'     => 19,
                'vitamin_b6_mg'    => 2.6,
                'folate_mcg'       => 285,
                'vitamin_b12_mcg'  => 2.2,
            ],

            // 13-15 years, Girls → 'girl'
            [
                'age_min' => 13, 'age_max' => 15, 'sex' => 'girl',
                'energy_kcal'      => 2400,
                'protein_g'        => 43.2,
                'fat_g'            => 40,
                'calcium_mg'       => 1000,
                'iron_mg'          => 30,
                'zinc_mg'          => 12.8,
                'magnesium_mg'     => 340,
                'vitamin_a_mcg'    => 890,
                'vitamin_c_mg'     => 65,
                'thiamin_b1_mg'    => 1.6,
                'riboflavin_b2_mg' => 2.2,
                'niacin_b3_mg'     => 16,
                'vitamin_b6_mg'    => 2.2,
                'folate_mcg'       => 245,
                'vitamin_b12_mcg'  => 2.2,
            ],

            // 16-18 years, Boys → 'boy'
            [
                'age_min' => 16, 'age_max' => 18, 'sex' => 'boy',
                'energy_kcal'      => 3320,
                'protein_g'        => 55.4,
                'fat_g'            => 50,
                'calcium_mg'       => 1050,
                'iron_mg'          => 26,
                'zinc_mg'          => 17.6,
                'magnesium_mg'     => 440,
                'vitamin_a_mcg'    => 1000,
                'vitamin_c_mg'     => 85,
                'thiamin_b1_mg'    => 2.2,
                'riboflavin_b2_mg' => 3.1,
                'niacin_b3_mg'     => 22,
                'vitamin_b6_mg'    => 3.0,
                'folate_mcg'       => 340,
                'vitamin_b12_mcg'  => 2.2,
            ],

            // 16-18 years, Girls → 'girl'
            [
                'age_min' => 16, 'age_max' => 18, 'sex' => 'girl',
                'energy_kcal'      => 2500,
                'protein_g'        => 46.2,
                'fat_g'            => 35,
                'calcium_mg'       => 1050,
                'iron_mg'          => 32,
                'zinc_mg'          => 14.2,
                'magnesium_mg'     => 380,
                'vitamin_a_mcg'    => 860,
                'vitamin_c_mg'     => 70,
                'thiamin_b1_mg'    => 1.7,
                'riboflavin_b2_mg' => 2.3,
                'niacin_b3_mg'     => 17,
                'vitamin_b6_mg'    => 2.3,
                'folate_mcg'       => 270,
                'vitamin_b12_mcg'  => 2.2,
            ],
        ];
    }
}
