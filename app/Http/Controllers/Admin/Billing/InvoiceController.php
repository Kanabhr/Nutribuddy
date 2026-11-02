<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Models\Order;
use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use \App\Http\Controllers\Admin\ResourceWritesDisabled;
    use \App\Http\Controllers\Admin\ResourceIndexFallbacks;
    /**
     * Display a listing of the invoices.
     */
    public function index(Request $request)
    {
        $orders = Order::where('status', '!=', 'cancelled')
            ->when($request->search, function($q, $search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('invoice_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%");
            })
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->filled('product_id'), fn ($q) => $q->whereHas('items', fn ($items) => $items->where('product_id', $request->product_id)))
            ->latest('placed_at')
            ->paginate(15)->withQueryString();

        return view('admin.billing.invoices.index', ['orders' => $orders, 'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name'])]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $invoice)
    {
        $invoice->load(['user', 'items.product.images', 'items.productVariant', 'coupon', 'payments']);

        return view('admin.billing.invoices.show', ['order' => $invoice]);
    }
}
