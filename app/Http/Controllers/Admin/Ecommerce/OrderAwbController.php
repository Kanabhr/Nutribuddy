<?php

namespace App\Http\Controllers\Admin\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderAwbController extends Controller
{
    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'awb_code' => ['required', 'string', 'max:100'],
            'courier_name' => ['nullable', 'string', 'max:100'],
        ]);

        $order->forceFill([
            'awb_code' => trim($data['awb_code']),
            'courier_name' => filled($data['courier_name'] ?? null) ? trim($data['courier_name']) : $order->courier_name,
        ])->save();

        $order->statusHistories()->create([
            'from_status' => $order->status,
            'to_status' => $order->status,
            'from_fulfillment_status' => $order->fulfillment_status,
            'to_fulfillment_status' => $order->fulfillment_status,
            'updated_by' => $request->user('admin')->id,
            'note' => 'AWB set to '.$order->awb_code.($order->courier_name ? ' ('.$order->courier_name.')' : '').'.',
        ]);

        return back()->with('success', 'AWB saved.');
    }
}