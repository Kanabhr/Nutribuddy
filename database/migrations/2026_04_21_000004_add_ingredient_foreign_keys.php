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
        Schema::table('ingredients', function (Blueprint $table) {
            $table->foreign('ingredient_category_id')->references('id')->on('ingredient_categories')->nullOnDelete();
        });

        Schema::table('ingredient_benefits', function (Blueprint $table) {
            $table->foreign('ingredient_id')->references('id')->on('ingredients')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingredient_benefits', function (Blueprint $table) {
            $table->dropForeign(['ingredient_id']);
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropForeign(['ingredient_category_id']);
        });
    }
};
