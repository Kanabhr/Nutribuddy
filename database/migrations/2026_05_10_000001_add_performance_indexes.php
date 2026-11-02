<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_active', 'is_featured', 'id'], 'products_storefront_listing_idx');
            $table->index(['is_active', 'category_id', 'base_price'], 'products_filter_idx');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->index(['product_id', 'is_primary', 'sort_order'], 'product_images_card_lookup_idx');
        });

        Schema::table('product_reviews', function (Blueprint $table) {
            $table->index(['product_id', 'is_active', 'rating'], 'product_reviews_rating_idx');
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->index(['product_id', 'product_variant_id'], 'inventories_product_variant_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'orders_user_created_idx');
            $table->index(['status', 'created_at'], 'orders_status_created_idx');
        });

        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->index(['coupon_id', 'user_id'], 'coupon_usages_user_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->dropIndex('coupon_usages_user_lookup_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_created_idx');
            $table->dropIndex('orders_user_created_idx');
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropIndex('inventories_product_variant_idx');
        });

        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropIndex('product_reviews_rating_idx');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropIndex('product_images_card_lookup_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_filter_idx');
            $table->dropIndex('products_storefront_listing_idx');
        });
    }
};
