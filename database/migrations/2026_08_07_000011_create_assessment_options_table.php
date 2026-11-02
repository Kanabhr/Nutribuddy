<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')
                ->constrained('assessment_questions')
                ->cascadeOnDelete();
            $table->string('option_text');
            $table->tinyInteger('score')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['question_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_options');
    }
};
