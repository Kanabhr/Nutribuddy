<?php

namespace App\Services\Ecommerce;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\OrderReturnItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Validates returnable quantities and creates return records atomically.
 */
class OrderReturnService
{
    /**
     * @param array{reason: string, comments?: string|null, items: array<int, array{order_item_id: int, quantity: int}>} $validated
     * @param array<int, UploadedFile> $attachments
     * @return array{success: bool, message?: string, errors?: array<int, string>, return?: OrderReturn}
     */
    public function create(Order $order, array $validated, array $attachments = []): array
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($order, $validated, $attachments, &$storedPaths): array {
                $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($lockedOrder->status !== 'delivered') {
                    return ['success' => false, 'message' => 'Return allowed only for delivered orders.'];
                }

                $returnLines = collect($validated['items'])
                    ->mapWithKeys(fn ($line) => [(int) $line['order_item_id'] => (int) $line['quantity']]);
                $orderItems = OrderItem::query()
                    ->where('order_id', $lockedOrder->id)
                    ->whereIn('id', $returnLines->keys())
                    ->lockForUpdate()
                    ->with('returnItems.returnRequest')
                    ->get()
                    ->keyBy('id');

                $invalidLines = [];
                $validLines = [];

                foreach ($returnLines as $orderItemId => $quantity) {
                    /** @var OrderItem|null $item */
                    $item = $orderItems->get($orderItemId);
                    if (! $item) {
                        $invalidLines[] = 'Invalid item selected.';
                        continue;
                    }

                    $alreadyRequested = $item->returnItems
                        ->filter(fn ($returnItem) => in_array($returnItem->returnRequest?->status, ['pending', 'approved', 'completed'], true))
                        ->sum('quantity');
                    $available = max(0, (int) $item->quantity - (int) $alreadyRequested);

                    if ($quantity > $available) {
                        $invalidLines[] = "{$item->product_name} has only {$available} returnable quantity left.";
                        continue;
                    }

                    $validLines[] = [$item, $quantity];
                }

                if ($invalidLines || empty($validLines)) {
                    return [
                        'success' => false,
                        'message' => $invalidLines[0] ?? 'Please select at least one return item.',
                        'errors' => $invalidLines ?: ['Please select at least one return item.'],
                    ];
                }

                foreach ($attachments as $file) {
                    $storedPaths[] = $file->store('returns', 'public');
                }

                $orderReturn = OrderReturn::create([
                    'order_id' => $lockedOrder->id,
                    'return_number' => 'RET-' . now()->format('Ymd') . strtoupper(Str::random(5)),
                    'reason' => $validated['reason'] . (! empty($validated['comments']) ? ': ' . $validated['comments'] : ''),
                    'status' => 'pending',
                    'refund_amount' => collect($validLines)->sum(
                        fn ($line) => round(((float) $line[0]->line_total / max(1, (int) $line[0]->quantity)) * (int) $line[1], 2)
                    ),
                    'media_paths' => $storedPaths ?: null,
                ]);

                foreach ($validLines as [$item, $quantity]) {
                    $unitRefund = (float) $item->line_total / max(1, (int) $item->quantity);
                    OrderReturnItem::create([
                        'order_return_id' => $orderReturn->id,
                        'order_item_id' => $item->id,
                        'product_name' => $item->product_name,
                        'sku' => $item->sku,
                        'quantity' => $quantity,
                        'unit_price' => $item->unit_price,
                        'line_total' => round($unitRefund * $quantity, 2),
                    ]);
                }

                return ['success' => true, 'return' => $orderReturn->load('items.orderItem')];
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);
            throw $exception;
        }
    }
}
