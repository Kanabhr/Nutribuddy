<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diet_subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('plans_remaining')->default(1)->after('amount_paid');
        });
    }

    public function down(): void
    {
        Schema::table('diet_subscriptions', function (Blueprint $table) {
            $table->dropColumn('plans_remaining');
        });
    }
};
