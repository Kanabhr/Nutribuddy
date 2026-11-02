<?php

namespace App\Services\Ecommerce;

use App\Models\OrderReturn;
use Illuminate\Support\Facades\DB;

/** Applies return review decisions and reconciles fully returned orders. */
class OrderReturnAdminService
{
    /** @param array{status: string, refund_amount: int|float|string, admin_note?: string|null} $data */
    public function updateStatus(OrderReturn $orderReturn, array $data, int $adminId): OrderReturn
    {
        return DB::transaction(function () use ($orderReturn, $data, $adminId): OrderReturn {
            $orderReturn = OrderReturn::query()->whereKey($orderReturn->id)->lockForUpdate()->firstOrFail();
            $orderReturn->update($data);

            $order = $orderReturn->order()->with('payments')->lockForUpdate()->first();
            if (! $order) {
                return $orderReturn;
            }

            if ($orderReturn->status === 'approved') {
                $order->update(['admin_note' => $data['admin_note'] ?? $order->admin_note]);
            }

            if ($orderReturn->status === 'completed') {
                $order->load('items.returnItems.returnRequest');
                $orderedQty = (int) $order->items->sum('quantity');
                $returnedQty = (int) $order->items->sum(function ($item): int {
                    return $item->returnItems
                        ->filter(fn ($returnItem) => $returnItem->returnRequest?->status === 'completed')
                        ->sum('quantity');
                });
                $isFullyReturned = $returnedQty >= $orderedQty;

                if ($isFullyReturned) {
                    $order->update([
                        'status' => 'returned',
                        'payment_status' => $order->payment_method === 'cod' ? 'refunded' : $order->payment_status,
                    ]);
                }

                $latestPayment = $order->payments()->latest()->first();
                if ($isFullyReturned && $latestPayment && $order->payment_method === 'cod') {
                    $latestPayment->update([
                        'status' => 'refunded',
                        'notes' => trim(($latestPayment->notes ?? '') . ' | Return completed and refund processed'),
                    ]);
                }

                if ($isFullyReturned) {
                    $order->statusHistories()->create([
                        'from_status' => 'delivered',
                        'to_status' => 'returned',
                        'from_fulfillment_status' => $order->fulfillment_status,
                        'to_fulfillment_status' => $order->fulfillment_status,
                        'updated_by' => $adminId,
                        'note' => 'Order marked returned after all quantities were returned.',
                    ]);
                }
            }

            return $orderReturn;
        });
    }
}
