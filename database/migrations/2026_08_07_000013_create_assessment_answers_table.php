<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')
                ->constrained('assessment_attempts')
                ->cascadeOnDelete();
            $table->foreignId('question_id')
                ->constrained('assessment_questions')
                ->cascadeOnDelete();
            $table->foreignId('option_id')
                ->constrained('assessment_options')
                ->cascadeOnDelete();
            $table->tinyInteger('obtained_score')->default(0);
            $table->timestamps();

            $table->index('attempt_id');
            $table->unique(['attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_answers');
    }
};
