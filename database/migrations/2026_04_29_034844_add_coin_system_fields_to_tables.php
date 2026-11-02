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
        if (!Schema::hasColumn('products', 'coins_reward')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('coins_reward')->default(0)->after('base_price');
            });
        }

        if (!Schema::hasColumn('users', 'coins_balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('coins_balance')->default(0)->after('role');
            });
        }

        if (!Schema::hasColumn('orders', 'coins_redeemed')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->integer('coins_redeemed')->default(0)->after('grand_total');
                $table->decimal('coin_discount', 10, 2)->default(0)->after('coins_redeemed');
            });
        }

        if (!Schema::hasTable('coin_transactions')) {
            Schema::create('coin_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
                $table->enum('type', ['earned', 'spent', 'refunded', 'bonus']);
                $table->integer('amount');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_transactions');

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'coins_redeemed')) {
                    $table->dropColumn(['coins_redeemed', 'coin_discount']);
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'coins_balance')) {
                    $table->dropColumn('coins_balance');
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'coins_reward')) {
                    $table->dropColumn('coins_reward');
                }
            });
        }
    }
};
