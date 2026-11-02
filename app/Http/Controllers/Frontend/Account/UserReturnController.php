<?php

namespace App\Http\Controllers\Frontend\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\ReturnStoreRequest;
use App\Mail\ReturnRequestCustomerMail;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use App\Notifications\NewReturnNotification;
use App\Services\Ecommerce\OrderReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class UserReturnController extends Controller
{
    public function index(Request $request)
    {
        $returns = OrderReturn::with(['order', 'items.orderItem'])
            ->whereHas('order', fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()
            ->paginate(10);

        if ($request->wantsJson()) {
            return response()->json($returns);
        }

        return view('frontend.account.returns.index', compact('returns'));
    }
    public function store(ReturnStoreRequest $request, Order $order, OrderReturnService $returns)
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 403);
        
        if ($order->status !== 'delivered') {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Return allowed only for delivered orders.',
                ], 422);
            }

            return back()->with('error', 'Return allowed only for delivered orders.');
        }

        $validated = $request->validated();

        $result = $returns->create($order, $validated, $request->file('attachments', []));

        if (! $result['success']) {
            $message = $result['message'] ?? 'Return allowed only for delivered orders.';
            if ($request->wantsJson()) {
                $response = ['message' => $message];
                if (isset($result['errors'])) {
                    $response['errors'] = ['items' => $result['errors']];
                }
                return response()->json($response, 422);
            }

            return back()->with('error', $message);
        }

        $orderReturn = $result['return'];

        // Email to customer
        if ($request->user()->email) {
            try {
                Mail::to($request->user()->email)->queue(new ReturnRequestCustomerMail($orderReturn));
            } catch (\Exception $e) {}
        }

        // Notify admins
        try {
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new NewReturnNotification($orderReturn->load('order')));
            }
        } catch (\Exception $e) {}

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Return request submitted successfully.',
                'return' => $orderReturn,
            ], 201);
        }

        return redirect()->route('user.orders.detail-page', $order)->with('success', 'Return request submitted successfully.');
    }
}
