<?php

namespace App\Services\Checkout;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CheckoutSummaryService
{
    public function __construct(private readonly PricingService $pricingService)
    {
    }

    public function forUser(User $user, array $validated): array
    {
        $coupon = $this->resolveCoupon($validated['coupon_code'] ?? null, $user->id);
        $cart = $this->resolveUserCart($user)
            ->load(['items.product.taxRate', 'items.product.primaryImage', 'items.product.images', 'items.productVariant']);

        abort_if($cart->items->isEmpty(), 422, 'Cart is empty.');

        // Always use the user's full balance — PricingService caps it to the allowed % automatically.
        // This makes coin redemption fully automatic without relying on client-sent values.
        $coinsToRedeem = (int) $user->coins_balance;

        $shippingState = null;
        if (!empty($validated['address_id'])) {
            $shippingState = \App\Models\CustomerAddress::where('user_id', $user->id)
                ->where('id', $validated['address_id'])
                ->value('state');
        } elseif (!empty($validated['shipping_state'])) {
            $shippingState = (string) $validated['shipping_state'];
        } else {
            $shippingState = \App\Models\CustomerAddress::where('user_id', $user->id)
                ->where('is_default_shipping', true)
                ->value('state') ?? \App\Models\CustomerAddress::where('user_id', $user->id)->latest()->value('state');
        }

        $pricing = $this->pricingService->calculate($cart->items, $coupon, $coinsToRedeem, $shippingState);
        $this->assertCouponMinimum($coupon, (float) $pricing['subtotal']);

        return [
            'cart' => $cart,
            'coupon' => $coupon,
            'pricing' => $pricing,
            'user_coins' => (int) $user->coins_balance,
            'checkout_token' => (string) Str::uuid(),
        ];
    }

    public function forGuest(array $items, ?string $couponCode = null, ?string $shippingState = null): array
    {
        $coupon = $this->resolveCoupon($couponCode, auth()->id());
        $cartItems = $this->guestCartItems($items);

        abort_if($cartItems->isEmpty(), 422, 'Cart is empty.');

        $pricing = $this->pricingService->calculate($cartItems, $coupon, 0, $shippingState);
        $this->assertCouponMinimum($coupon, (float) $pricing['subtotal']);

        return [
            'cart' => ['items' => $cartItems],
            'coupon' => $coupon,
            'pricing' => $pricing,
        ];
    }

    public function resolveCoupon(?string $couponCode, ?int $userId): ?Coupon
    {
        $couponCode = trim((string) $couponCode);
        if ($couponCode === '') {
            return null;
        }

        $coupon = Coupon::whereRaw('UPPER(code) = ?', [strtoupper($couponCode)])->first();
        abort_if(! $coupon || ! $coupon->isCurrentlyValid($userId), 422, 'Invalid or expired coupon code.');

        return $coupon;
    }

    public function assertCouponMinimum(?Coupon $coupon, float $subtotal): void
    {
        if ($coupon && $coupon->min_order_amount !== null && $subtotal < (float) $coupon->min_order_amount) {
            abort(422, 'Coupon minimum order amount not met.');
        }
    }

    private function resolveUserCart(User $user): Cart
    {
        return Cart::firstOrCreate(
            ['user_id' => $user->id],
            ['currency' => 'INR']
        );
    }

    private function guestCartItems(array $items): Collection
    {
        $cartItems = collect();

        foreach ($items as $item) {
            if (empty($item['product_id']) || empty($item['quantity'])) {
                continue;
            }

            $product = Product::with(['taxRate', 'primaryImage', 'images'])->find($item['product_id']);
            if (! $product) {
                continue;
            }

            $variant = null;
            if (! empty($item['product_variant_id'])) {
                $variant = ProductVariant::where('product_id', $product->id)
                    ->where('is_active', true)
                    ->find($item['product_variant_id']);
            }

            $cartItem = new CartItem([
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'quantity' => (int) $item['quantity'],
            ]);

            $cartItem->setRelation('product', $product);
            $cartItem->setRelation('productVariant', $variant);

            $cartItems->push($cartItem);
        }

        return $cartItems;
    }
}
