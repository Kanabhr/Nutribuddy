<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_section_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')
                ->constrained('assessment_attempts')
                ->cascadeOnDelete();
            $table->string('section');
            $table->unsignedSmallInteger('score')->default(0);
            $table->unsignedSmallInteger('max_score')->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->timestamps();

            $table->index('attempt_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_section_scores');
    }
};
