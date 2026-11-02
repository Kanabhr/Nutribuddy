<?php

namespace App\Http\Controllers\Admin\Ecommerce;

use App\Mail\ReturnStatusUpdatedMail;
use App\Models\OrderReturn;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Ecommerce\UpdateOrderReturnRequest;
use App\Services\Ecommerce\OrderReturnAdminService;
use Illuminate\Support\Facades\Mail;

class OrderReturnController extends Controller
{
    use \App\Http\Controllers\Admin\ResourceWritesDisabled;
    use \App\Http\Controllers\Admin\ResourceIndexFallbacks;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $returns = OrderReturn::with(['order.user', 'items'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('admin.ecommerce.returns.index', compact('returns'));
    }

    /**
     * Display the specified resource.
     */
    public function show(OrderReturn $orderReturn)
    {
        $orderReturn->load('items.orderItem.product', 'order.items.returnItems.returnRequest', 'order.items.product', 'order.user');
        return view('admin.ecommerce.returns.show', compact('orderReturn'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderReturnRequest $request, OrderReturn $orderReturn, OrderReturnAdminService $returns)
    {
        $validated = $request->validated();
        $orderReturn = $returns->updateStatus($orderReturn, $validated, (int) $request->user()->id);

        $customerEmail = $orderReturn->order?->customer_email;
        if ($customerEmail) {
            Mail::to($customerEmail)->queue(new ReturnStatusUpdatedMail($orderReturn->fresh('order')));
        }

        return redirect()->route('admin.ecommerce.order-returns.index')->with('success', 'Order return status updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderReturn $orderReturn)
    {
        $orderReturn->delete();
        return redirect()->route('admin.ecommerce.order-returns.index')->with('success', 'Order return record deleted.');
    }
}
