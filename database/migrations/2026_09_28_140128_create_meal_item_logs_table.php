<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_item_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('meal_id')->nullable();
            $table->unsignedBigInteger('food_id');
            $table->date('log_date');
            $table->boolean('is_consumed')->default(true);
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->unique(['child_id', 'meal_id', 'food_id', 'log_date'], 'unique_meal_item_log');
            $table->index(['child_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_item_logs');
    }
};