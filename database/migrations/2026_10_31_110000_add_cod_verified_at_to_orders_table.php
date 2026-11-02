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
        if (! Schema::hasColumn('orders', 'cod_verified_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->timestamp('cod_verified_at')->nullable()->after('placed_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('orders', 'cod_verified_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('cod_verified_at');
            });
        }
    }
};

