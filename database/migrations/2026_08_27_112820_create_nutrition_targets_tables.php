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
        Schema::create('icmr_nutrition_targets', function (Blueprint $table) {
            $table->id();
            $table->integer('age_min');
            $table->integer('age_max');
            $table->enum('sex', ['boy', 'girl', 'all'])->default('all');
            
            // Targets
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
            $table->timestamps();
        });

        Schema::create('nutrition_tips', function (Blueprint $table) {
            $table->id();
            $table->string('benefit'); // e.g. "Brain & Focus"
            $table->integer('age_min')->nullable();
            $table->integer('age_max')->nullable();
            $table->enum('sex', ['boy', 'girl', 'all'])->default('all');
            $table->text('combination'); // e.g. "Iron-rich food + Vitamin C food"
            $table->text('science_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nutrition_tips');
        Schema::dropIfExists('icmr_nutrition_targets');
    }
};
