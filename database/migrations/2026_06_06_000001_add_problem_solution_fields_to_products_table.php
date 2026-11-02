<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('ps_brand_title')->nullable()->after('card_hover_image_path');
            $table->string('ps_product_title')->nullable()->after('ps_brand_title');
            $table->json('ps_tagline_items')->nullable()->after('ps_product_title');
            $table->string('ps_left_label')->nullable()->after('ps_tagline_items');
            $table->json('ps_left_cards')->nullable()->after('ps_left_label');
            $table->string('ps_center_image')->nullable()->after('ps_left_cards');
            $table->string('ps_right_label')->nullable()->after('ps_center_image');
            $table->json('ps_right_cards')->nullable()->after('ps_right_label');
            $table->string('ps_shelf_left_image')->nullable()->after('ps_right_cards');
            $table->string('ps_shelf_right_image')->nullable()->after('ps_shelf_left_image');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'ps_brand_title',
                'ps_product_title',
                'ps_tagline_items',
                'ps_left_label',
                'ps_left_cards',
                'ps_center_image',
                'ps_right_label',
                'ps_right_cards',
                'ps_shelf_left_image',
                'ps_shelf_right_image',
            ]);
        });
    }
};
