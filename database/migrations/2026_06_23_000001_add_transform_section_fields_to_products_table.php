<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('transform_description')->nullable()->after('ps_shelf_right_image');
            $table->string('transform_main_image')->nullable()->after('transform_description');
            $table->json('transform_results')->nullable()->after('transform_main_image');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'transform_description',
                'transform_main_image',
                'transform_results',
            ]);
        });
    }
};
