<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add missing columns to food_master for diet chart generation
        Schema::table('food_master', function (Blueprint $table) {
            if (!Schema::hasColumn('food_master', 'best_fit_goal')) {
                $table->string('best_fit_goal')->nullable()->after('active');
            }
            if (!Schema::hasColumn('food_master', 'goal_tags')) {
                $table->json('goal_tags')->nullable()->after('best_fit_goal');
            }
            if (!Schema::hasColumn('food_master', 'repeat_gap_days')) {
                $table->integer('repeat_gap_days')->default(3)->after('goal_tags');
            }
        });

        // Ensure food_nutrients has all required nutrient columns
        Schema::table('food_nutrients', function (Blueprint $table) {
            // Check and add missing columns
            $missingColumns = [
                'potassium_mg' => 'float',
                'sodium_mg' => 'float',
            ];

            foreach ($missingColumns as $column => $type) {
                if (!Schema::hasColumn('food_nutrients', $column)) {
                    $table->{$type}($column)->default(0)->after('vitamin_b12_mcg');
                }
            }
        });

        // Add plan_data column to diet_plans if missing
        Schema::table('diet_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('diet_plans', 'plan_data')) {
                $table->json('plan_data')->nullable()->after('ai_summary');
            }
        });

        // Ensure all required columns exist in child_assessments
        Schema::table('child_assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('child_assessments', 'diet_type')) {
                $table->string('diet_type')->nullable()->after('main_goal');
            }
            if (!Schema::hasColumn('child_assessments', 'allergies')) {
                $table->json('allergies')->nullable()->after('diet_type');
            }
            if (!Schema::hasColumn('child_assessments', 'region')) {
                $table->string('region')->nullable()->after('allergies');
            }
            if (!Schema::hasColumn('child_assessments', 'budget')) {
                $table->string('budget')->nullable()->after('region');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food_master', function (Blueprint $table) {
            $table->dropColumnIfExists(['best_fit_goal', 'goal_tags', 'repeat_gap_days']);
        });

        Schema::table('food_nutrients', function (Blueprint $table) {
            $table->dropColumnIfExists(['potassium_mg', 'sodium_mg']);
        });

        Schema::table('diet_plans', function (Blueprint $table) {
            $table->dropColumnIfExists('plan_data');
        });

        Schema::table('child_assessments', function (Blueprint $table) {
            $table->dropColumnIfExists(['diet_type', 'allergies', 'region', 'budget']);
        });
    }
};
