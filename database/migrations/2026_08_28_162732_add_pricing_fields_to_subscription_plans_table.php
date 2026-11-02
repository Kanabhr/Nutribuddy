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
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->decimal('monthly_price', 10, 2)->nullable()->after('price');
            $table->decimal('quarterly_price', 10, 2)->nullable()->after('monthly_price');
            $table->decimal('annual_price', 10, 2)->nullable()->after('quarterly_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['monthly_price', 'quarterly_price', 'annual_price']);
        });
    }
};
