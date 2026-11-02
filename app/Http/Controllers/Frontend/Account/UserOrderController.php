<?php

namespace App\Http\Controllers\Frontend\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\Account\CancelOrderRequest;
use App\Http\Requests\Frontend\Account\OrderHistoryFilterRequest;
use App\Mail\OrderStatusUpdatedMail;
use App\Models\Order;
use App\Services\Ecommerce\OrderWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class UserOrderController extends Controller
{
    public function detailPage(Request $request, Order $order): View
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id || $request->user()->role === 'admin', 403);
        $order->load([
            'items.product',
            'items.productVariant',
            'items.returnItems.returnRequest',
            'payments',
            'statusHistories.updatedBy',
            'returns',
        ]);

        return view('frontend.account.orders.show', [
            'order' => $order,
        ]);
    }

    public function index(OrderHistoryFilterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (
            ($validated['date_from'] ?? null)
            && ($validated['date_to'] ?? null)
            && $validated['date_from'] > $validated['date_to']
        ) {
            return response()->json([
                'message' => 'The end date must be after or equal to the start date.',
            ], 422);
        }

        $orders = Order::with(['items.returnItems.returnRequest'])
            ->withCount('items')
            ->where('user_id', $request->user()->id)
            ->when($validated['date_from'] ?? null, function ($query, string $dateFrom) {
                $query->where(function ($dateQuery) use ($dateFrom) {
                    $dateQuery
                        ->whereDate('placed_at', '>=', $dateFrom)
                        ->orWhere(function ($fallbackQuery) use ($dateFrom) {
                            $fallbackQuery
                                ->whereNull('placed_at')
                                ->whereDate('created_at', '>=', $dateFrom);
                        });
                });
            })
            ->when($validated['date_to'] ?? null, function ($query, string $dateTo) {
                $query->where(function ($dateQuery) use ($dateTo) {
                    $dateQuery
                        ->whereDate('placed_at', '<=', $dateTo)
                        ->orWhere(function ($fallbackQuery) use ($dateTo) {
                            $fallbackQuery
                                ->whereNull('placed_at')
                                ->whereDate('created_at', '<=', $dateTo);
                        });
                });
            })
            ->latest()
            ->paginate(10);

        $orders->getCollection()->transform(function (Order $order) {
            $order->items->each(function ($item) {
                $alreadyRequested = $item->returnItems
                    ->filter(fn ($returnItem) => in_array($returnItem->returnRequest?->status, ['pending', 'approved', 'completed'], true))
                    ->sum('quantity');

                $item->setAttribute('returnable_quantity', max(0, (int) $item->quantity - (int) $alreadyRequested));
                $item->setAttribute('returned_quantity', (int) $alreadyRequested);
            });

            return $order;
        });

        return response()->json($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 403);

        $order->load([
            'items.product',
            'items.productVariant',
            'payments',
            'statusHistories.updatedBy',
            'returns',
        ]);

        return response()->json($order);
    }

    public function cancel(CancelOrderRequest $request, Order $order, OrderWorkflowService $workflow): JsonResponse
    {
        if (! $workflow->cancelByCustomer($order, (int) $request->user()->id)) {
            return response()->json([
                'message' => 'This order cannot be cancelled at the current stage.',
            ], 422);
        }

        if ($order->customer_email) {
            Mail::to($order->customer_email)->queue(new OrderStatusUpdatedMail($order));
        }

        return response()->json([
            'message' => 'Order cancelled successfully.',
            'order' => $order->fresh(),
        ]);
    }
}
