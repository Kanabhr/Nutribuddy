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
        Schema::table('categories', function (Blueprint $table) {
            $table->text('meta_keywords')->nullable()->after('meta_description');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->text('meta_keywords')->nullable()->after('meta_description');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->text('meta_keywords')->nullable()->after('meta_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('meta_keywords');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('meta_keywords');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn('meta_keywords');
        });
    }
};
