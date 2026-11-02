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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shiprocket_order_id')->nullable()->after('status');
            $table->string('shiprocket_shipment_id')->nullable()->after('shiprocket_order_id');
            $table->string('awb_code')->nullable()->after('shiprocket_shipment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shiprocket_order_id', 'shiprocket_shipment_id', 'awb_code']);
        });
    }
};
