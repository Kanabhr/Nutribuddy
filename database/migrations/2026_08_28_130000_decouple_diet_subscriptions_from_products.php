<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create the new standalone subscription plans table
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price', 10, 2);
            $table->enum('level', ['basic', 'plus', 'pro', 'premium'])->default('basic');
            $table->boolean('is_active')->default(true);
            $table->json('features')->nullable();
            $table->timestamps();
        });

        // 2. Modify diet_subscriptions to use subscription_plans and native payment fields
        Schema::table('diet_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['order_id']);
            
            $table->dropColumn(['product_id', 'order_id']);

            $table->foreignId('subscription_plan_id')->after('child_id')->nullable()->constrained('subscription_plans')->onDelete('set null');
            
            $table->string('payment_status')->default('pending')->after('status');
            $table->string('razorpay_order_id')->nullable()->after('payment_status');
            $table->string('razorpay_payment_id')->nullable()->after('razorpay_order_id');
            $table->decimal('amount_paid', 10, 2)->default(0)->after('razorpay_payment_id');
        });

        // 3. Drop is_diet_service from products
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_diet_service');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_diet_service')->default(false)->after('is_variant_enabled');
        });

        Schema::table('diet_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['subscription_plan_id']);
            $table->dropColumn([
                'subscription_plan_id',
                'payment_status',
                'razorpay_order_id',
                'razorpay_payment_id',
                'amount_paid'
            ]);

            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
        });

        Schema::dropIfExists('subscription_plans');
    }
};
