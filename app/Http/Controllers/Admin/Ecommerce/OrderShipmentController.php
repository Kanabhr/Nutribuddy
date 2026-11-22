<?php

namespace App\Http\Controllers\Admin\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Shipping\ShipmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

/** Admin shipping actions for one order (Shiprocket). Routes require the "manage orders" permission. */
class OrderShipmentController extends Controller
{
    public function push(Order $order, ShipmentService $shipments): RedirectResponse
    {
        return $this->run(fn () => $shipments->push($order), 'Order sent to Shiprocket.');
    }

    public function assignAwb(Request $request, Order $order, ShipmentService $shipments): RedirectResponse
    {
        $data = $request->validate(['courier_id' => ['nullable', 'integer', 'min:1']]);

        return $this->run(
            fn () => $shipments->assignAwb($order, isset($data['courier_id']) ? (int) $data['courier_id'] : null),
            'Courier and AWB assigned.'
        );
    }

    public function pickup(Order $order, ShipmentService $shipments): RedirectResponse
    {
        return $this->run(fn () => $shipments->schedulePickup($order), 'Pickup scheduled.');
    }

    public function label(Order $order, ShipmentService $shipments): RedirectResponse
    {
        return $this->run(fn () => $shipments->generateLabel($order), 'Label generated.');
    }

    public function track(Order $order, ShipmentService $shipments): RedirectResponse
    {
        $new = 0;

        return $this->run(function () use ($order, $shipments, &$new) {
            $new = $shipments->refreshTracking($order);
        }, fn () => $new > 0 ? "Tracking updated ({$new} new steps)." : 'Tracking is up to date.');
    }

    public function cancel(Order $order, ShipmentService $shipments): RedirectResponse
    {
        return $this->run(fn () => $shipments->cancel($order), 'Shiprocket order cancelled.');
    }

    public function ndr(Request $request, Order $order, ShipmentService $shipments): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:re-attempt,return'],
            'comments' => ['nullable', 'string', 'max:255'],
            'deferred_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        return $this->run(
            fn () => $shipments->ndrAction($order, $data['action'], $data['comments'] ?? null, $data['deferred_date'] ?? null),
            'NDR action sent to Shiprocket.'
        );
    }

    /** @param string|callable(): string $success */
    private function run(callable $action, string|callable $success): RedirectResponse
    {
        try {
            $action();
        } catch (RuntimeException $e) {
            // Precondition and Shiprocket messages are written to be shown to admins.
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Shiprocket request failed. Check the logs and try again.');
        }

        return back()->with('success', is_callable($success) ? $success() : $success);
    }
}
