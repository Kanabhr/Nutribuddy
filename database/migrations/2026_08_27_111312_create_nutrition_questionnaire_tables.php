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
        Schema::create('questionnaire_questions', function (Blueprint $table) {
            $table->id();
            $table->string('model')->default('basic'); // basic, plus, pro, premium
            $table->string('section')->nullable(); // A. Child & Growth, etc.
            $table->string('question');
            $table->string('answer_type'); // select, multi-select, text, number
            $table->json('show_if')->nullable(); // {"age": "<6"}
            $table->string('clinical_reason')->nullable();
            $table->string('chart_effect')->nullable();
            $table->boolean('is_optional')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('questionnaire_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questionnaire_questions')->onDelete('cascade');
            $table->string('option_text');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('questionnaire_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_assessment_id')->constrained('child_assessments')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('questionnaire_questions')->onDelete('cascade');
            $table->json('answer_value'); // Can be string, number, or array for multi-select
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questionnaire_answers');
        Schema::dropIfExists('questionnaire_options');
        Schema::dropIfExists('questionnaire_questions');
    }
};
