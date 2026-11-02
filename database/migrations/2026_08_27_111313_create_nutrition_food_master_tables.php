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
        Schema::create('food_master', function (Blueprint $table) {
            $table->id();
            $table->string('food_name');
            $table->string('regional_name')->nullable();
            $table->string('category')->nullable();
            $table->string('sub_category')->nullable();
            
            // New classification fields
            $table->string('meal_slot')->nullable(); // Breakfast, Lunch, Dinner, Snack
            $table->string('food_group')->nullable(); // Pulse, Cereal, Dairy, Fruit, etc.
            $table->string('season')->default('Year-round'); // Summer, Winter, Monsoon
            
            // Hard filters
            $table->boolean('veg_status')->default(true);
            $table->boolean('vegan')->default(false);
            $table->boolean('jain_allowed')->default(false);
            $table->boolean('contains_egg')->default(false);
            $table->boolean('contains_dairy')->default(false);
            $table->boolean('contains_nuts')->default(false);
            $table->boolean('contains_gluten')->default(false);
            
            $table->string('cost_band')->nullable(); // economy, moderate, flexible
            $table->string('region')->nullable();
            
            // Serving and recipe info
            $table->string('serving')->nullable(); // e.g. "1 serving"
            $table->float('serving_g')->nullable(); // e.g. 150
            
            $table->json('ingredients')->nullable();
            $table->json('method')->nullable();
            
            $table->integer('age_min')->nullable();
            $table->integer('age_max')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('food_nutrients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('food_id')->constrained('food_master')->onDelete('cascade');
            // Macro and Micro Nutrients per serving
            $table->float('energy_kcal')->default(0);
            $table->float('protein_g')->default(0);
            $table->float('fat_g')->default(0);
            $table->float('fiber_g')->default(0);
            $table->float('calcium_mg')->default(0);
            $table->float('iron_mg')->default(0);
            $table->float('zinc_mg')->default(0);
            $table->float('magnesium_mg')->default(0);
            $table->float('vitamin_a_mcg')->default(0);
            $table->float('vitamin_c_mg')->default(0);
            $table->float('vitamin_d_mcg')->default(0);
            $table->float('thiamin_b1_mg')->default(0);
            $table->float('riboflavin_b2_mg')->default(0);
            $table->float('niacin_b3_mg')->default(0);
            $table->float('vitamin_b6_mg')->default(0);
            $table->float('folate_mcg')->default(0);
            $table->float('vitamin_b12_mcg')->default(0);
            $table->float('potassium_mg')->default(0);
            $table->float('sodium_mg')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_nutrients');
        Schema::dropIfExists('food_master');
    }
};
