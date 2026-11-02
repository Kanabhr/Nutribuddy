<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('child_assessments', function (Blueprint $table) {
            $table->string('meal_pattern')->nullable()->after('region');
            $table->unsignedTinyInteger('dairy_glasses')->nullable()->after('meal_pattern');
            $table->string('cooking_medium')->nullable()->after('dairy_glasses');
            $table->text('medical_condition')->nullable()->after('cooking_medium');
            $table->decimal('bmi', 5, 2)->nullable()->after('medical_condition');
            $table->string('growth_profile')->default('unknown')->after('bmi');
            $table->boolean('professional_review_required')->default(false)->after('growth_profile');
        });
    }

    public function down(): void
    {
        Schema::table('child_assessments', function (Blueprint $table) {
            $table->dropColumn(['meal_pattern', 'dairy_glasses', 'cooking_medium', 'medical_condition', 'bmi', 'growth_profile', 'professional_review_required']);
        });
    }
};
