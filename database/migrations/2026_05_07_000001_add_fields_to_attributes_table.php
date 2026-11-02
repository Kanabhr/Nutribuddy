<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            if (! Schema::hasColumn('attributes', 'name')) {
                $table->string('name')->after('id');
            }

            if (! Schema::hasColumn('attributes', 'slug')) {
                $table->string('slug')->unique()->after('name');
            }

            if (! Schema::hasColumn('attributes', 'values')) {
                $table->json('values')->nullable()->after('slug');
            }

            if (! Schema::hasColumn('attributes', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('values');
            }

            if (! Schema::hasColumn('attributes', 'position')) {
                $table->unsignedInteger('position')->default(0)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            foreach (['position', 'is_active', 'values', 'slug', 'name'] as $column) {
                if (Schema::hasColumn('attributes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
