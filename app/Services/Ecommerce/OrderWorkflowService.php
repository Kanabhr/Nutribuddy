<?php

namespace App\Services\Ecommerce;

use App\Events\OrderPaid;
use App\Models\Order;
use App\Support\OrderFlow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/** Coordinates order state changes, payment effects, and audit history. */
class OrderWorkflowService
{
    /** @param array{status: string, fulfillment_status?: string|null, payment_status?: string|null, admin_note?: string|null} $data */
    public function updateStatusByAdmin(Order $order, array $data, int $adminId): bool
    {
        $orderBecamePaid = false;
        $updated = DB::transaction(function () use ($order, $data, $adminId, &$orderBecamePaid): bool {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $data['fulfillment_status'] = $data['fulfillment_status'] ?? $order->fulfillment_status;
            $data['payment_status'] = $data['payment_status'] ?? $order->payment_status;

            if (! OrderFlow::canAdminMoveTo($order->status, $data['status'])) {
                return false;
            }

            $previousStatus = $order->status;
            $previousFulfillmentStatus = $order->fulfillment_status;
            $previousPaymentStatus = $order->payment_status;
            $order->update($data);

            if ($order->status === 'delivered' && $order->payment_method === 'cod' && $order->payment_status === 'pending') {
                $order->update(['payment_status' => 'paid']);
                $latestPayment = $order->payments()->latest()->lockForUpdate()->first();
                $latestPayment?->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'notes' => trim(($latestPayment->notes ?? '').' | Marked paid on delivery by admin'),
                ]);
            }

            $orderBecamePaid = $previousPaymentStatus !== 'paid' && $order->payment_status === 'paid';

            $order->statusHistories()->create([
                'from_status' => $previousStatus,
                'to_status' => $data['status'],
                'from_fulfillment_status' => $previousFulfillmentStatus,
                'to_fulfillment_status' => $data['fulfillment_status'],
                'updated_by' => $adminId,
                'note' => $data['admin_note'] ?? null,
            ]);

            return true;
        });

        if ($updated) {
            $order->refresh();
        }

        if ($orderBecamePaid) {
            Event::dispatch(new OrderPaid($order));
        }

        return $updated;
    }

    public function cancelByCustomer(Order $order, int $userId): bool
    {
        $cancelled = DB::transaction(function () use ($order, $userId): bool {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (! OrderFlow::canMoveTo($order->status, 'cancelled')) {
                return false;
            }

            $previousStatus = $order->status;
            $previousFulfillmentStatus = $order->fulfillment_status;
            $order->update([
                'status' => 'cancelled',
                'fulfillment_status' => $previousFulfillmentStatus === 'fulfilled' ? 'fulfilled' : 'unfulfilled',
                'admin_note' => 'Cancelled by customer',
            ]);
            $order->statusHistories()->create([
                'from_status' => $previousStatus,
                'to_status' => 'cancelled',
                'from_fulfillment_status' => $previousFulfillmentStatus,
                'to_fulfillment_status' => $order->fulfillment_status,
                'updated_by' => $userId,
                'note' => 'Cancelled by customer from user panel.',
            ]);

            return true;
        });

        if ($cancelled) {
            $order->refresh();
        }

        return $cancelled;
    }
}
