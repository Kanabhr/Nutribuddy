<?php

use App\Models\Order;
use App\Services\Ecommerce\InvoiceNumberService;
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
        if (! Schema::hasColumn('orders', 'invoice_number')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('invoice_number', 50)->nullable()->unique()->after('order_number');
            });
        }

        // Backfill existing orders in chronological order
        $orders = Order::whereNull('invoice_number')->orderBy('id')->get();
        if ($orders->isNotEmpty()) {
            $service = new InvoiceNumberService();
            foreach ($orders as $order) {
                $order->invoice_number = $service->generateNextInvoiceNumber($order->placed_at ?: $order->created_at);
                $order->saveQuietly();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('orders', 'invoice_number')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('invoice_number');
            });
        }
    }
};

