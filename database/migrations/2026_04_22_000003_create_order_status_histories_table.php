<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('from_fulfillment_status')->nullable();
            $table->string('to_fulfillment_status')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['updated_by']);
        });

        Schema::dropIfExists('order_status_histories');
    }
};
