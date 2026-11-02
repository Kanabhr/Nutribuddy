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
        Schema::table('questionnaire_questions', function (Blueprint $table) {
            $table->string('options_scale')->nullable()->after('answer_type');
            $table->string('only_ask_if')->nullable()->after('chart_effect');
            // We can keep show_if around if it's used elsewhere, but let's just add only_ask_if for simplicity.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questionnaire_questions', function (Blueprint $table) {
            $table->dropColumn('options_scale');
            $table->dropColumn('only_ask_if');
        });
    }
};
