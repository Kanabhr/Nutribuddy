<?php

namespace App\Providers;

use App\Models\Coupon;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Observers\AuditableObserver;
use Illuminate\Support\ServiceProvider;

/**
 * Single registration point for all audit observers.
 *
 * Tracked models:
 *  - Order          : status, payment_status, fulfillment_status changes
 *  - OrderReturn    : refund_amount, status changes
 *  - Payment        : status, amount (refund flows)
 *  - Coupon         : discount, expiry, active state changes
 *  - Setting        : site / payment / auth config changes (secrets masked)
 *  - Inventory      : stock_qty changes (covers StockService which calls update()
 *                     directly — the observer fires on model update regardless of
 *                     whether the call came from a service or a controller)
 *  - ProductVariant : price changes
 *
 * Product and ProductVariant price/stock edits are owned by another agent (admin product
 * controllers). Inventory is registered here to catch StockService::adjustStock() which
 * uses $inventory->update() — that fires the Eloquent updated event normally.
 */
class AuditServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $observer = AuditableObserver::class;

        Order::observe($observer);
        OrderReturn::observe($observer);
        Payment::observe($observer);
        Coupon::observe($observer);
        Setting::observe($observer);
        Inventory::observe($observer);
        ProductVariant::observe($observer);
                Product::observe($observer);
        Role::observe($observer);

        // Successful admin logins only. Failed logins fire a different event and are not logged.
        Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
            if ($event->guard !== 'admin') {
                return;
            }
            try {
                app(\App\Services\Audit\AuditService::class)->record($event->user, 'login', [], ['ip' => request()->ip()]);
            } catch (\Throwable $e) {
                // Never let an audit failure block a login.
            }
        });
    }
}
