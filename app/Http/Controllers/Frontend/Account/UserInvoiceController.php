<?php

namespace App\Http\Controllers\Frontend\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->where('status', '!=', 'cancelled')
            ->latest()
            ->paginate(15);

        return view('frontend.account.invoices', compact('orders'));
    }

    public function page(Request $request, Order $order): View
    {
        $this->authorizeOrder($request, $order);
        $order->load(['items.product', 'items.productVariant', 'payments']);

        return view('frontend.account.orders.invoice', [
            'order' => $order,
            'invoiceNumber' => $order->invoice_number ?: ('INV-' . $order->order_number),
        ]);
    }

    public function download(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);
        $order->load(['items.product', 'items.productVariant', 'payments']);
        $invoiceNumber = $order->invoice_number ?: ('INV-' . $order->order_number);
        $filename = str_replace(['/', '\\'], '-', $invoiceNumber) . '.pdf';

        return Pdf::loadView('pdf.invoices.order', [
            'order' => $order,
            'invoiceNumber' => $invoiceNumber,
        ])->setPaper('a4')->download($filename);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);
        $order->load(['items', 'payments']);

        return response()->json([
            'invoice_number' => $order->invoice_number ?: ('INV-' . $order->order_number),
            'order' => $order,
            'billing' => [
                'name' => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
            ],
            'shipping' => [
                'name' => $order->shipping_name,
                'phone' => $order->shipping_phone,
                'line_1' => $order->shipping_address_line_1,
                'line_2' => $order->shipping_address_line_2,
                'landmark' => $order->shipping_landmark,
                'city' => $order->shipping_city,
                'state' => $order->shipping_state,
                'postal_code' => $order->shipping_postal_code,
                'country' => $order->shipping_country,
            ],
            'totals' => [
                'subtotal' => $order->subtotal,
                'discount_total' => $order->discount_total,
                'coins_redeemed' => $order->coins_redeemed,
                'coin_discount' => $order->coin_discount,
                'tax_total' => $order->tax_total,
                'gst_total' => $order->gst_total,
                'cgst_total' => $order->cgst_total,
                'sgst_total' => $order->sgst_total,
                'igst_total' => $order->igst_total,
                'shipping_total' => $order->shipping_total,
                'grand_total' => $order->grand_total,
            ],
        ]);
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        abort_unless(
            (int) $order->user_id === (int) $request->user()->id || $request->user()->role === 'admin',
            403
        );
    }
}
