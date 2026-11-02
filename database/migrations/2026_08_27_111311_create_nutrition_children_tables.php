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
        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Assuming parents are users
            $table->string('name');
            $table->date('dob')->nullable();
            $table->enum('sex', ['boy', 'girl']);
            $table->timestamps();
        });

        Schema::create('child_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children')->onDelete('cascade');
            $table->string('model_level'); // basic, plus, pro, premium
            $table->float('current_weight_kg')->nullable();
            $table->float('current_height_cm')->nullable();
            $table->string('main_goal')->nullable();
            $table->string('diet_type')->nullable(); // vegetarian, vegan, jain, etc.
            $table->json('allergies')->nullable(); // array of allergies
            $table->string('region')->nullable();
            $table->string('budget')->nullable(); // economy, moderate, flexible
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('child_assessments');
        Schema::dropIfExists('children');
    }
};
