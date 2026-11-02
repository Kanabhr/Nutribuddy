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
        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            $table->string('dish_id', 20)->unique();
            $table->string('dish_name', 50)->nullable();
            $table->string('slot', 10)->nullable();
            $table->string('also_suitable_for', 25)->nullable();
            $table->string('region', 10)->nullable();
            $table->string('food_group', 19)->nullable();
            $table->string('diet_type', 12)->nullable();
            $table->string('variant_type', 12)->nullable();
            $table->double('serving_g')->nullable();
            $table->string('household_measure', 19)->nullable();
            $table->double('energy_density_kcal_per_g')->nullable();
            $table->integer('cook_min')->nullable();
            $table->string('cost_band', 10)->nullable();
            $table->string('taste', 12)->nullable();
            $table->string('texture', 10)->nullable();
            $table->integer('kid_accept_1_5')->nullable();
            $table->string('tiffin_safe', 10)->nullable();
            $table->string('reheat_ok', 10)->nullable();
            $table->integer('repeat_gap_days')->nullable();
            $table->string('digestive_load', 10)->nullable();
            $table->string('activity_fit', 22)->nullable();
            $table->integer('n_ingredients')->nullable();
            $table->string('is_veg', 10)->nullable();
            $table->string('is_vegan', 10)->nullable();
            $table->string('is_jain_ok', 10)->nullable();
            $table->string('contains_dairy', 10)->nullable();
            $table->string('contains_gluten', 10)->nullable();
            $table->string('contains_egg', 10)->nullable();
            $table->string('contains_nuts', 10)->nullable();
            $table->string('contains_soy', 10)->nullable();
            $table->string('contains_sesame', 10)->nullable();
            $table->string('contains_honey', 10)->nullable();
            $table->double('energy_kcal')->nullable();
            $table->double('protein_g')->nullable();
            $table->double('fat_g')->nullable();
            $table->double('sfa_g')->nullable();
            $table->double('mufa_g')->nullable();
            $table->double('pufa_g')->nullable();
            $table->double('ala_omega3_mg')->nullable();
            $table->double('omega6_mg')->nullable();
            $table->double('carb_g')->nullable();
            $table->double('free_sugar_g')->nullable();
            $table->double('fibre_total_g')->nullable();
            $table->double('fibre_sol_g')->nullable();
            $table->string('fibre_type', 16)->nullable();
            $table->string('gut_effect', 10)->nullable();
            $table->double('protein_pct_energy')->nullable();
            $table->double('fat_pct_energy')->nullable();
            $table->integer('dish_gi')->nullable();
            $table->string('gi_band', 10)->nullable();
            $table->double('calcium_mg')->nullable();
            $table->double('iron_mg')->nullable();
            $table->double('heme_iron_mg')->nullable();
            $table->string('iron_type', 10)->nullable();
            $table->double('zinc_mg')->nullable();
            $table->double('magnesium_mg')->nullable();
            $table->double('phosphorus_mg')->nullable();
            $table->double('potassium_mg')->nullable();
            $table->double('sodium_mg')->nullable();
            $table->string('sodium_band', 10)->nullable();
            $table->double('selenium_ug')->nullable();
            $table->double('iodine_ug')->nullable();
            $table->double('copper_mg')->nullable();
            $table->double('vit_a_rae_ug')->nullable();
            $table->double('vit_d_ug')->nullable();
            $table->double('vit_e_mg')->nullable();
            $table->double('vit_k_ug')->nullable();
            $table->double('vit_c_mg')->nullable();
            $table->double('b1_mg')->nullable();
            $table->double('b2_mg')->nullable();
            $table->double('b3_mg')->nullable();
            $table->double('b6_mg')->nullable();
            $table->double('folate_ug')->nullable();
            $table->double('b12_ug')->nullable();
            $table->double('choline_mg')->nullable();
            $table->double('phytate_mg')->nullable();
            $table->double('oxalate_mg')->nullable();
            $table->double('protein_per_100kcal')->nullable();
            $table->integer('calcium_per_100kcal')->nullable();
            $table->double('iron_per_100kcal')->nullable();
            $table->string('best_fit_goal', 19)->nullable();
            $table->string('goal_tags', 158)->nullable();
            $table->double('goal_height_growth_pct')->nullable();
            $table->double('goal_weight_gain_pct')->nullable();
            $table->double('goal_immunity_pct')->nullable();
            $table->double('goal_brain_focus_pct')->nullable();
            $table->double('goal_gut_constipation_pct')->nullable();
            $table->double('goal_iron_anemia_pct')->nullable();
            $table->double('goal_calcium_bone_pct')->nullable();
            $table->double('goal_vitamin_d_pct')->nullable();
            $table->double('goal_b12_pct')->nullable();
            $table->double('goal_zinc_pct')->nullable();
            $table->double('goal_sports_activity_pct')->nullable();
            $table->double('goal_exam_focus_pct')->nullable();
            $table->integer('goal_picky_eater_accept_pct')->nullable();
            $table->integer('goal_weight_mgmt_index')->nullable();
            $table->text('method')->nullable();
            $table->text('ingredient_list_per_serving')->nullable();
            $table->string('local_name', 20)->nullable();
            
            $table->index('dish_name');
            $table->index('food_group');
            $table->index('slot');
            $table->index('diet_type');
            $table->index('is_veg');
            $table->index('region');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('foods');
    }
};
