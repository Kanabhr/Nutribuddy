<?php

namespace App\Http\Controllers\Admin\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Ecommerce\StoreOrderPaymentAdjustmentRequest;
use App\Models\Order;
use App\Services\Ecommerce\PaymentAdjustmentService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class OrderPaymentAdjustmentController extends Controller
{
    public function store(StoreOrderPaymentAdjustmentRequest $request, Order $order, PaymentAdjustmentService $adjustments): RedirectResponse
    {
        try {
            $adjustments->adjust($order, $request->validated(), $request->user('admin'), $request->ip());
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payment adjustment recorded.');
    }
}