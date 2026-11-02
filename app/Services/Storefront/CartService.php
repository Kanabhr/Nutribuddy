<?php

namespace App\Services\Storefront;

use App\Models\Cart;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function contents(Request $request, PricingService $pricingService): array
    {
        $cart = $this->resolveUserCart($request);
        $this->assignDefaultVariantsToLegacyRows($cart);
        $this->consolidateCartProductRows($cart);
        $cart->load([
            'items.product.taxRate',
            'items.product.primaryImage',
            'items.product.images',
            'items.product.inventory',
            'items.productVariant.inventory',
        ]);
        $pricing = $pricingService->calculate($cart->items);

        $cart->items->each(function ($item) {
            $inventory = $item->product_variant_id
                ? ($item->productVariant?->inventory ?? $item->product?->inventory)
                : $item->product?->inventory;
            $item->available_stock = $inventory?->track_stock
                ? max(0, (int) $inventory->stock_qty - (int) $inventory->reserved_qty)
                : null;
        });

        return compact('cart', 'pricing');
    }

    public function addItem(Request $request, array $validated): Cart
    {
        $product = Product::where('is_active', true)->findOrFail($validated['product_id']);
        $variantId = $validated['product_variant_id'] ?? null;

        if ($variantId) {
            ProductVariant::where('product_id', $product->id)
                ->where('is_active', true)
                ->findOrFail($variantId);
        }

        return DB::transaction(function () use ($request, $product, $variantId, $validated) {
            $cart = $this->resolveUserCart($request);
            Cart::whereKey($cart->id)->lockForUpdate()->first();
            $matchingItems = $cart->items()
                ->where('product_id', $product->id)
                ->when($variantId, fn ($q) => $q->where('product_variant_id', $variantId), fn ($q) => $q->whereNull('product_variant_id'))
                ->lockForUpdate()
                ->orderBy('id')
                ->get();
            $item = $matchingItems->first();
            $quantity = (int) $validated['quantity'];

            if ($item) {
                $newQuantity = (int) $matchingItems->sum('quantity') + $quantity;
                $this->assertInventoryForQuantity($product->id, $variantId, $newQuantity);
                $item->update(['product_variant_id' => $variantId, 'quantity' => $newQuantity]);
                $duplicateIds = $matchingItems->where('id', '!=', $item->id)->pluck('id');
                if ($duplicateIds->isNotEmpty()) {
                    $cart->items()->whereIn('id', $duplicateIds)->delete();
                }
            } else {
                $this->assertInventoryForQuantity($product->id, $variantId, $quantity);
                $cart->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variantId,
                    'quantity' => $quantity,
                ]);
            }

            return $cart;
        }, 3);
    }

    public function updateItem(Request $request, int $itemId, int $quantity): Cart
    {
        return DB::transaction(function () use ($request, $itemId, $quantity) {
            $cart = $this->resolveUserCart($request);
            Cart::whereKey($cart->id)->lockForUpdate()->first();
            $item = $cart->items()->lockForUpdate()->findOrFail($itemId);
            $this->assertInventoryForQuantity($item->product_id, $item->product_variant_id, $quantity);
            $item->update(['quantity' => $quantity]);

            return $cart;
        }, 3);
    }

    public function removeItem(Request $request, int $itemId): Cart
    {
        return DB::transaction(function () use ($request, $itemId) {
            $cart = $this->resolveUserCart($request);
            Cart::whereKey($cart->id)->lockForUpdate()->first();
            $cart->items()->lockForUpdate()->findOrFail($itemId)->delete();

            return $cart;
        }, 3);
    }

    public function response(Cart $cart, PricingService $pricingService, string $message): array
    {
        $cart->load(['items.product.taxRate', 'items.product.primaryImage', 'items.product.images', 'items.productVariant']);

        return [
            'message' => $message,
            'cart' => $cart,
            'pricing' => $pricingService->calculate($cart->items),
            'cart_count' => (int) $cart->items->sum('quantity'),
        ];
    }

    private function resolveUserCart(Request $request): Cart
    {
        return Cart::firstOrCreate(['user_id' => $request->user()->id], ['currency' => 'INR']);
    }

    private function consolidateCartProductRows(Cart $cart): void
    {
        $cart->loadMissing('items');
        $cart->items
            ->groupBy(fn ($item) => $item->product_id . '::' . ($item->product_variant_id ?: 'base'))
            ->filter(fn ($items) => $items->count() > 1)
            ->each(function ($items) use ($cart) {
                $primary = $items->sortBy('id')->first();
                $variantId = $items->first(fn ($item) => $item->product_variant_id)?->product_variant_id;
                $primary->update([
                    'product_variant_id' => $primary->product_variant_id ?: $variantId,
                    'quantity' => (int) $items->sum('quantity'),
                ]);
                $cart->items()->whereIn('id', $items->where('id', '!=', $primary->id)->pluck('id'))->delete();
            });
        $cart->unsetRelation('items');
    }

    private function assignDefaultVariantsToLegacyRows(Cart $cart): void
    {
        $legacyItems = $cart->items()->whereNull('product_variant_id')->with('product.variants')->get();
        foreach ($legacyItems as $item) {
            if (! $item->product?->is_variant_enabled) {
                continue;
            }
            $variant = $item->product->variants->where('is_active', true)->sortBy([
                ['is_default', 'desc'], ['position', 'asc'], ['id', 'asc'],
            ])->first();
            if (! $variant) {
                continue;
            }
            $existing = $cart->items()->where('product_id', $item->product_id)
                ->where('product_variant_id', $variant->id)->first();
            if ($existing) {
                $existing->increment('quantity', (int) $item->quantity);
                $item->delete();
                continue;
            }
            $item->update(['product_variant_id' => $variant->id]);
        }
        $cart->unsetRelation('items');
    }

    private function assertInventoryForQuantity(int $productId, ?int $variantId, int $requestedQuantity): void
    {
        $inventory = Inventory::where('product_id', $productId)
            ->when($variantId, fn ($q) => $q->where('product_variant_id', $variantId), fn ($q) => $q->whereNull('product_variant_id'))
            ->first();
        if (! $inventory || ! $inventory->track_stock) {
            return;
        }
        $available = max(0, (int) $inventory->stock_qty - (int) $inventory->reserved_qty);
        abort_if($available < $requestedQuantity, 422, 'Requested quantity is not available in stock.');
    }
}
