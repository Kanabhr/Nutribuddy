<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Order matters:
     *  1. QuestionnaireQuestionsSeeder — must run before DeficiencyRulesSeeder
     *     so Q26 option text is established (RiskFlaggingEngine depends on it).
     *  2. IcmrTargetsSeeder            — standalone; upserts ICMR RDA table.
     *  3. DeficiencyRulesSeeder        — seeds deficiency_rules (no deficiency_foods;
     *     those require the food_master SQL import to run first — see docs/seeder-notes.md).
     *  4. NutritionTipsSeeder          — seeds all 35 nutrition_tips rows.
     *
     * NOT called here:
     *  - FoodSeeder: references a hardcoded path on a different machine (C:\Users\Ritik\...)
     *    and targets the legacy food_master table; neutralised as a safe no-op.
     *  - Plan/food data: comes from the imported SQL file, not this seeder.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            RolePermissionSeeder::class,
            AssessmentSeeder::class,
            QuestionnaireQuestionsSeeder::class,
            IcmrTargetsSeeder::class,
            DeficiencyRulesSeeder::class,
            NutritionTipsSeeder::class,
        ]);
    }
}
