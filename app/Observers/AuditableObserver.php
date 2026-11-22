<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Audit\AuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Generic observer registered once per tracked model in AuditServiceProvider.
 * Logs only changed attributes on update (never the full row).
 * Skips logging when the audit_logs table itself is not yet available
 * (e.g. during early migration runs).
 *
 * Retention note: audit_logs is append-only and has no automatic expiry.
 * On a busy store (1 k orders/day, avg 3 status changes each) expect ~1 k–3 k rows/day
 * from orders alone, plus inventory, payment and coupon events. At ~500 bytes/row
 * that is roughly 500 KB–1.5 MB/day — manageable for years at that volume.
 * Add a scheduled artisan command to prune rows older than your retention policy
 * (e.g. 2 years) before the table reaches tens of millions of rows.
 */
class AuditableObserver
{
    /**
     * Fields allowed in created events for Order.
     * Excludes customer PII (name, phone, email, address lines).
     *
     * @var array<string>
     */
    private const ORDER_CREATED_WHITELIST = [
        'id',
        'order_number',
        'invoice_number',
        'status',
        'fulfillment_status',
        'payment_status',
        'payment_method',
        'currency',
        'coupon_code',
        'subtotal',
        'tax_total',
        'discount_total',
        'shipping_total',
        'grand_total',
        'coins_redeemed',
        'coin_discount',
        'placed_at',
        'created_at',
    ];

    public function __construct(private readonly AuditService $audit) {}

    public function created(Model $model): void
    {
        $attrs = $model->getAttributes();

        if ($model instanceof Order) {
            $attrs = array_intersect_key($attrs, array_flip(self::ORDER_CREATED_WHITELIST));
        }

        $this->safeRecord($model, 'created', [], $attrs);
    }

    public function updated(Model $model): void
    {
        $changed = $model->getChanges(); // only the fields that actually changed
        if (empty($changed)) {
            return;
        }
        $old = array_intersect_key($model->getOriginal(), $changed);
        $this->safeRecord($model, 'updated', $old, $changed);
    }

    public function deleted(Model $model): void
    {
        $this->safeRecord($model, 'deleted', $model->getOriginal(), []);
    }

    private function safeRecord(Model $model, string $action, array $old, array $new): void
    {
        try {
            if (! Schema::hasTable('audit_logs')) {
                return;
            }
            app(AuditService::class)->record($model, $action, $old, $new);
        } catch (\Throwable $e) {
            // Never let audit failure break the primary request.
            Log::warning('Audit log error: '.$e->getMessage());
        }
    }
}
