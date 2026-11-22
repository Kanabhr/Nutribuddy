<?php

namespace App\Services\Ecommerce;

use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\OrderPaymentAdjustment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RuntimeException;

/**
 * Manual payment adjustments made by an admin (point 33).
 *
 * - Records the change in the order_payment_adjustments ledger and updates orders.payment_status.
 * - Never touches the payments table, which holds gateway/COD transactions.
 * - Does not move money. Refunds are made in the gateway or bank first; the reference is recorded here.
 * - Marking an order paid dispatches OrderPaid like a gateway payment does (coins, diet plan activation).
 */
class PaymentAdjustmentService
{
    /** Refund amount already recorded through manual adjustments. Refunds done by the return flow are not included. */
    public function manuallyRefunded(Order $order): float
    {
        return (float) OrderPaymentAdjustment::query()
            ->where('order_id', $order->id)
            ->whereIn('type', ['refund', 'partial_refund'])
            ->sum('amount');
    }

    public function refundable(Order $order): float
    {
        return max(0.0, round((float) $order->grand_total - $this->manuallyRefunded($order), 2));
    }

    /**
     * @param  array{type: string, amount?: float|int|string|null, reason: string, reference: string}  $data
     *
     * @throws RuntimeException when the adjustment is not allowed for the order's current state
     */
    public function adjust(Order $order, array $data, User $admin, ?string $ip = null): OrderPaymentAdjustment
    {
        $becamePaid = false;

        $adjustment = DB::transaction(function () use ($order, $data, $admin, $ip, &$becamePaid): OrderPaymentAdjustment {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $from = (string) $order->payment_status;

            [$to, $amount] = match ($data['type']) {
                'mark_paid' => $this->markPaid($order),
                'refund' => $this->fullRefund($order),
                'partial_refund' => $this->partialRefund($order, $data['amount'] ?? null),
                default => throw new RuntimeException('Unknown adjustment type.'),
            };

            $order->update(['payment_status' => $to]);

            $adjustment = OrderPaymentAdjustment::create([
                'order_id' => $order->id,
                'type' => $data['type'],
                'amount' => $amount,
                'from_payment_status' => $from,
                'to_payment_status' => $to,
                'reason' => trim($data['reason']),
                'reference' => trim($data['reference']),
                'adjusted_by' => $admin->id,
                'adjusted_by_name' => $admin->name,
                'ip_address' => $ip,
                'created_at' => now(),
            ]);

            // Shows up in the order's Status Timeline with the admin's name.
            $order->statusHistories()->create([
                'from_status' => $order->status,
                'to_status' => $order->status,
                'from_fulfillment_status' => $order->fulfillment_status,
                'to_fulfillment_status' => $order->fulfillment_status,
                'updated_by' => $admin->id,
                'note' => sprintf(
                    'Payment %s to %s (manual, INR %s). Ref: %s. Reason: %s',
                    str_replace('_', ' ', $from),
                    str_replace('_', ' ', $to),
                    number_format($amount, 2, '.', ''),
                    $adjustment->reference,
                    $adjustment->reason
                ),
            ]);

            $becamePaid = $from !== 'paid' && $to === 'paid';

            return $adjustment;
        });

        if ($becamePaid) {
            Event::dispatch(new OrderPaid($order->refresh()));
        }

        return $adjustment;
    }

    /** @return array{0: string, 1: float} */
    private function markPaid(Order $order): array
    {
        if (! in_array($order->payment_status, ['pending', 'failed'], true)) {
            throw new RuntimeException('Only pending or failed payments can be marked as paid.');
        }
        if ($order->status === 'cancelled') {
            throw new RuntimeException('A cancelled order cannot be marked as paid.');
        }

        return ['paid', (float) $order->grand_total];
    }

    /** @return array{0: string, 1: float} */
    private function fullRefund(Order $order): array
    {
        $this->requireRefundableState($order);

        return ['refunded', $this->refundable($order)];
    }

    /** @return array{0: string, 1: float} */
    private function partialRefund(Order $order, mixed $amount): array
    {
        $this->requireRefundableState($order);

        $amount = round((float) $amount, 2);
        $remaining = $this->refundable($order);

        if ($amount <= 0) {
            throw new RuntimeException('Enter the refund amount.');
        }
        if ($amount >= $remaining) {
            throw new RuntimeException(sprintf(
                'A partial refund must be less than the remaining refundable amount (INR %s). Use a full refund instead.',
                number_format($remaining, 2, '.', '')
            ));
        }

        return ['partially_refunded', $amount];
    }

    private function requireRefundableState(Order $order): void
    {
        if (! in_array($order->payment_status, ['paid', 'partially_refunded'], true)) {
            throw new RuntimeException('Only paid or partially refunded orders can be refunded.');
        }
        if ($this->refundable($order) <= 0) {
            throw new RuntimeException('Nothing is left to refund on this order.');
        }
    }
}