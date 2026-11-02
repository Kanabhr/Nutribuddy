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
        Schema::create('deficiency_rules', function (Blueprint $table) {
            $table->id();
            $table->string('sign'); // e.g., 'Pale inner eyelids/skin'
            $table->string('likely_gap'); // e.g., 'Iron / anemia risk'
            $table->string('confirmation_test')->nullable();
            $table->string('smart_combination')->nullable(); // e.g., 'Iron food + Vitamin C'
            $table->string('common_group')->nullable();
            $table->timestamps();
        });

        Schema::create('deficiency_foods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('deficiency_rules')->onDelete('cascade');
            $table->foreignId('food_id')->constrained('food_master')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('clinical_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_assessment_id')->constrained('child_assessments')->onDelete('cascade');
            $table->string('flag_name'); // e.g., 'Iron Risk'
            $table->string('reason'); // e.g., 'Due to tiredness and pale eyes'
            $table->string('priority')->default('low'); // low, medium, high
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinical_flags');
        Schema::dropIfExists('deficiency_foods');
        Schema::dropIfExists('deficiency_rules');
    }
};
