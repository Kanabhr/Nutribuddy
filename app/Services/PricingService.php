<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\Setting;
use App\Models\TaxRate;
use Illuminate\Support\Collection;

class PricingService
{
    public static function isSameState(?string $shippingState, ?string $storeState = null): bool
    {
        $storeState = $storeState ?: (string) Setting::get('store_state', 'Gujarat');

        if (empty(trim((string) $shippingState))) {
            return true; // Default to same state if address not yet selected
        }

        $normalize = function (string $val): string {
            $val = strtolower(trim($val));
            $stateMap = [
                'gj' => 'gujarat',
                'mh' => 'maharashtra',
                'dl' => 'delhi',
                'rj' => 'rajasthan',
                'mp' => 'madhya pradesh',
                'up' => 'uttar pradesh',
                'ka' => 'karnataka',
                'tn' => 'tamil nadu',
                'wb' => 'west bengal',
                'pb' => 'punjab',
                'hr' => 'haryana',
            ];
            $clean = (string) preg_replace('/[^a-z0-9]/', '', $val);
            return $stateMap[$clean] ?? $clean;
        };

        return $normalize($shippingState) === $normalize($storeState);
    }

    public function calculate(
        Collection $cartItems,
        ?Coupon $coupon = null,
        int $coinsToRedeem = 0,
        ?string $shippingState = null
    ): array
    {
        $subtotal = 0.0;
        $taxTotal = 0.0;
        $shippingTotal = 0.0;
        $lineItems = [];
        $totalCoinsEarned = 0;

        $storeState = (string) Setting::get('store_state', 'Gujarat');
        $isInterstate = !self::isSameState($shippingState, $storeState);

        // Variables for what the user sees in the checkout breakdown
        $displaySubtotal = 0.0;
        $hiddenTaxOnSubtotal = 0.0;
        $shownTaxOnSubtotal = 0.0;

        foreach ($cartItems as $cartItem) {
            /** @var Product $product */
            $product = $cartItem->product;
            if (! $product) {
                continue;
            }
            $variant = $cartItem->productVariant;
            $quantity = (int) $cartItem->quantity;
            $unitPrice = (float) ($variant?->price ?? $product->base_price);
            $lineSubTotal = $unitPrice * $quantity;

            /** @var TaxRate|null $taxRate */
            $taxRate = $product->taxRate;
            $taxPercent = (float) ($taxRate?->rate ?? 0);
            $showGstInCheckout = (bool) ($taxRate?->show_in_checkout ?? true);
            
            // Storefront prices are tax-exclusive, so apply GST ON TOP of the price.
            $lineTax = $taxPercent > 0
                ? ($lineSubTotal * $taxPercent) / 100
                : 0.0;
            $lineShipping = ((float) ($product->shipping_price ?? 0)) * $quantity;

            if ($isInterstate) {
                $lineCgst = 0.0;
                $lineSgst = 0.0;
                $lineIgst = $lineTax;
            } else {
                $lineCgst = $taxPercent > 0 ? ($lineSubTotal * ($taxPercent / 2)) / 100 : 0.0;
                $lineSgst = $lineTax - $lineCgst;
                $lineIgst = 0.0;
            }

            // Calculate Coins Earned for this line item
            $lineCoinsReward = (int) (!empty($product->coins_reward) ? $product->coins_reward : round($unitPrice * 0.05));
            $totalCoinsEarned += ($lineCoinsReward * $quantity);

            // Update actual totals
            $subtotal += $lineSubTotal;
            $taxTotal += $lineTax;
            $shippingTotal += $lineShipping;

            // Update display components
            if ($showGstInCheckout) {
                $displaySubtotal += $lineSubTotal;
                $shownTaxOnSubtotal += $lineTax;
            } else {
                // If hidden, add the tax directly into the subtotal (MRP) shown to the user
                $displaySubtotal += ($lineSubTotal + $lineTax);
                $hiddenTaxOnSubtotal += $lineTax;
            }

            // For itemized display
            $displayUnitPrice = $showGstInCheckout ? $unitPrice : ($unitPrice + ($unitPrice * $taxPercent) / 100);

            $lineItems[] = [
                'cart_item' => $cartItem,
                'quantity' => $quantity,
                'unit_price' => round($unitPrice, 0),
                'display_unit_price' => round($displayUnitPrice, 0),
                'display_line_total' => round($displayUnitPrice * $quantity, 0),
                'tax_percent' => round($taxPercent, 2),
                'cgst_rate' => $isInterstate ? 0.0 : round($taxPercent / 2, 2),
                'sgst_rate' => $isInterstate ? 0.0 : round($taxPercent / 2, 2),
                'igst_rate' => $isInterstate ? round($taxPercent, 2) : 0.0,
                'tax_code' => $taxRate?->code,
                'tax_amount' => round($lineTax, 0),
                'cgst_amount' => round($lineCgst, 0),
                'sgst_amount' => round($lineSgst, 0),
                'igst_amount' => round($lineIgst, 0),
                'show_gst_in_checkout' => $showGstInCheckout,
                'shipping_amount' => round($lineShipping, 0),
                'coins_earned' => $lineCoinsReward * $quantity,
            ];
        }

        // ══ COUPON LOGIC ══
        $discountTotal = 0.0;
        if ($coupon) {
            if ($coupon->discount_type === 'percentage') {
                // Apply percentage on the displayed subtotal (matches user expectations whether tax is hidden or not)
                $discountTotal = ($displaySubtotal * (float) $coupon->discount_value) / 100;
            } else {
                // Fixed amount: user wants the full value to be deducted
                $discountTotal = (float) $coupon->discount_value;
            }

            if ($coupon->max_discount_amount !== null) {
                $discountTotal = min($discountTotal, (float) $coupon->max_discount_amount);
            }
            
            // Limit discount to Subtotal + Tax (don't go negative)
            $discountTotal = min($discountTotal, ($subtotal + $taxTotal));
        }

        // ══ COIN REDEMPTION LOGIC ══
        $coinDiscount = 0.0;
        $coinsRedeemed = 0;
        $discountPercent = (int) Setting::get(
            'loyalty_max_redemption_percent',
            Setting::get('loyalty_discount_percentage', 10),
        );
        $coinToCashRate = max(1, (int) Setting::get('loyalty_conversion_rate', 10));
        $isLoyaltyEnabled = (bool) Setting::get('loyalty_enabled', 1);

        // Max coins that can be redeemed based on % of order total (before coupon)
        $maxAllowedCoinDiscount = (($subtotal + $taxTotal) * $discountPercent) / 100;
        // After coupon is applied, remaining order value
        $remainingBalance = ($subtotal + $taxTotal) - $discountTotal;
        $maxRedeemableCoins = (int) floor(max(0, min($maxAllowedCoinDiscount, $remainingBalance)) * $coinToCashRate);
        $adminCoinCap = (int) Setting::get('loyalty_max_redeemable_coins', 0);
        if ($adminCoinCap > 0) {
            $maxRedeemableCoins = min($maxRedeemableCoins, $adminCoinCap);
        }

        if ($isLoyaltyEnabled && $coinsToRedeem > 0) {
            $coinsRedeemed = min($coinsToRedeem, $maxRedeemableCoins);
            $coinDiscount = $coinsRedeemed / $coinToCashRate;
        }

        // ══ TOTALS CALCULATION ══
        // Display Subtotal and Tax are already accumulated correctly based on the `show_in_checkout` flag
        $displayTaxTotal = $shownTaxOnSubtotal;

        // Discount components for display
        $displayCouponDiscount = $discountTotal;
        $displayCoinDiscount = $coinDiscount;
        $displayDiscountTotal = $discountTotal + $coinDiscount;

        // Round components first to ensure no amount mismatch
        $roundedSubtotal = round($subtotal, 0);
        $roundedTaxTotal = round($taxTotal, 0);
        $roundedShippingTotal = round($shippingTotal, 0);
        $roundedDiscountTotal = round($discountTotal, 0);
        $roundedCoinDiscount = round($coinDiscount, 0);

        // Calculations for DB / Logic based on rounded values
        $grandTotal = ($roundedSubtotal + $roundedTaxTotal + $roundedShippingTotal) - $roundedDiscountTotal - $roundedCoinDiscount;
        $grandTotal = max(0, $grandTotal);

        return [
            'line_items' => $lineItems,
            'subtotal' => $roundedSubtotal,
            'tax_total' => $roundedTaxTotal,
            'discount_total' => $roundedDiscountTotal,
            'coin_discount' => $roundedCoinDiscount,
            'coins_redeemed' => $coinsRedeemed,
            'max_redeemable_coins' => $maxRedeemableCoins,
            'total_coins_earned' => $totalCoinsEarned,
            'display_subtotal' => round($displaySubtotal, 0),
            'display_tax_total' => round($displayTaxTotal, 0),
            'display_discount_total' => round($displayDiscountTotal, 0),
            'display_coupon_discount' => round($displayCouponDiscount, 0),
            'display_coin_discount' => round($displayCoinDiscount, 0),
            'is_interstate' => $isInterstate,
            'tax_type' => $isInterstate ? 'IGST' : 'CGST+SGST',
            'store_state' => $storeState,
            'shipping_state' => $shippingState,
            'gst_total' => $roundedTaxTotal,
            'cgst_total' => $isInterstate ? 0.0 : round($taxTotal / 2, 0),
            'sgst_total' => $isInterstate ? 0.0 : max(0.0, $roundedTaxTotal - round($taxTotal / 2, 0)),
            'igst_total' => $isInterstate ? $roundedTaxTotal : 0.0,
            'shipping_total' => $roundedShippingTotal,
            'grand_total' => $grandTotal,
        ];
    }
}
