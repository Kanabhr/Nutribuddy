<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('child_assessments', function (Blueprint $table) {
            $table->decimal('initial_weight_kg', 5, 2)->nullable()->after('current_height_cm');
            $table->decimal('initial_height_cm', 5, 2)->nullable()->after('initial_weight_kg');
        });

        // 🔥 Existing assessments ke liye: current = initial (agar initial khaali hai)
        DB::table('child_assessments')
            ->whereNull('initial_weight_kg')
            ->update([
                'initial_weight_kg' => DB::raw('current_weight_kg'),
                'initial_height_cm' => DB::raw('current_height_cm'),
            ]);
    }

    public function down(): void
    {
        Schema::table('child_assessments', function (Blueprint $table) {
            $table->dropColumn(['initial_weight_kg', 'initial_height_cm']);
        });
    }
};