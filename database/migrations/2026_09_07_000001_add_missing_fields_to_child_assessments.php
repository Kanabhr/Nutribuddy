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
        Schema::table('child_assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('child_assessments', 'picky_level')) {
                $table->string('picky_level')->nullable()->after('diet_type');
            }
            if (!Schema::hasColumn('child_assessments', 'activity_level')) {
                $table->string('activity_level')->nullable()->after('picky_level');
            }
            if (!Schema::hasColumn('child_assessments', 'current_season')) {
                $table->string('current_season')->nullable()->after('budget');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('child_assessments', function (Blueprint $table) {
            if (Schema::hasColumn('child_assessments', 'picky_level')) {
                $table->dropColumn('picky_level');
            }
            if (Schema::hasColumn('child_assessments', 'activity_level')) {
                $table->dropColumn('activity_level');
            }
            if (Schema::hasColumn('child_assessments', 'current_season')) {
                $table->dropColumn('current_season');
            }
        });
    }
};
