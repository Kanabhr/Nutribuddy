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
            $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->foreign('tax_rate_id')->references('id')->on('tax_rates')->nullOnDelete();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->cascadeOnDelete();
        });

        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('coupon_id')->references('id')->on('coupons')->nullOnDelete();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->foreign('blog_category_id')->references('id')->on('blog_categories')->nullOnDelete();
            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('contact_leads', function (Blueprint $table) {
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_leads', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropForeign(['blog_category_id']);
            $table->dropForeign(['author_id']);
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['product_id']);
            $table->dropForeign(['product_variant_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['coupon_id']);
        });

        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['product_variant_id']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['tax_rate_id']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
        });
    }
};
