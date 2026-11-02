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
        Schema::create('diet_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_assessment_id')->constrained('child_assessments')->onDelete('cascade');
            $table->integer('version')->default(1);
            $table->string('status')->default('active'); // active, archived
            $table->text('ai_summary')->nullable(); // The human-readable tips/warnings from LLM
            $table->timestamps();
        });

        Schema::create('diet_plan_meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diet_plan_id')->constrained('diet_plans')->onDelete('cascade');
            $table->string('meal_slot'); // Early Morning, Breakfast, etc.
            $table->string('timing')->nullable(); // approx time
            $table->string('focus')->nullable(); // reason/nutrient focus
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('diet_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diet_plan_meal_id')->constrained('diet_plan_meals')->onDelete('cascade');
            $table->foreignId('food_id')->constrained('food_master')->onDelete('cascade');
            $table->boolean('is_alternative')->default(false);
            $table->string('portion_size')->nullable();
            $table->timestamps();
        });

        Schema::create('growth_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children')->onDelete('cascade');
            $table->float('weight_kg');
            $table->float('height_cm');
            $table->date('recorded_at');
            $table->timestamps();
        });

        Schema::create('lab_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_assessment_id')->constrained('child_assessments')->onDelete('cascade');
            $table->float('hb')->nullable();
            $table->float('ferritin')->nullable();
            $table->float('vitamin_d')->nullable();
            $table->float('b12')->nullable();
            $table->float('tsh')->nullable();
            $table->date('report_date');
            $table->timestamps();
        });

        Schema::create('supplements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_assessment_id')->constrained('child_assessments')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplements');
        Schema::dropIfExists('lab_reports');
        Schema::dropIfExists('growth_records');
        Schema::dropIfExists('diet_plan_items');
        Schema::dropIfExists('diet_plan_meals');
        Schema::dropIfExists('diet_plans');
    }
};
