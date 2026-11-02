<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('fulfillment_status')->default('unfulfilled')->after('status');
            $table->string('coupon_code')->nullable()->after('coupon_id');
            $table->decimal('gst_total', 12, 2)->default(0)->after('tax_total');
            $table->decimal('cgst_total', 12, 2)->default(0)->after('gst_total');
            $table->decimal('sgst_total', 12, 2)->default(0)->after('cgst_total');
            $table->decimal('igst_total', 12, 2)->default(0)->after('sgst_total');
            $table->json('pricing_snapshot')->nullable()->after('grand_total');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('tax_code')->nullable()->after('tax_percent');
            $table->decimal('gst_amount', 12, 2)->default(0)->after('tax_amount');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['tax_code', 'gst_amount']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'fulfillment_status',
                'coupon_code',
                'gst_total',
                'cgst_total',
                'sgst_total',
                'igst_total',
                'pricing_snapshot',
            ]);
        });
    }
};
