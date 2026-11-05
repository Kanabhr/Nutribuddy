<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diet_plan_meals', function (Blueprint $table) {
            // 0-based index of the day within the plan (day 0, 1, … N-1).
            // Old 7-day plans have no value; nullable keeps them valid.
            $table->unsignedSmallInteger('day_index')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('diet_plan_meals', function (Blueprint $table) {
            $table->dropColumn('day_index');
        });
    }
};
