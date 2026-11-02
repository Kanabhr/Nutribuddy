<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // ICMR-NIN RDA 2020, children aged 4–6 years. Vitamin D is stored as µg (600 IU = 15 µg).
        DB::table('icmr_nutrition_targets')->updateOrInsert(
            ['age_min' => 4, 'age_max' => 6, 'sex' => 'all'],
            [
                'energy_kcal' => 1360, 'protein_g' => 16, 'fat_g' => 0, 'fiber_g' => 20,
                'calcium_mg' => 550, 'iron_mg' => 11, 'zinc_mg' => 4.5, 'magnesium_mg' => 125,
                'vitamin_a_mcg' => 510, 'vitamin_c_mg' => 35, 'vitamin_d_mcg' => 15,
                'thiamin_b1_mg' => 0.9, 'riboflavin_b2_mg' => 1.3, 'niacin_b3_mg' => 9,
                'vitamin_b6_mg' => 1.2, 'folate_mcg' => 135, 'vitamin_b12_mcg' => 2.2,
                'created_at' => now(), 'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('icmr_nutrition_targets')->where(['age_min' => 4, 'age_max' => 6, 'sex' => 'all'])->delete();
    }
};
