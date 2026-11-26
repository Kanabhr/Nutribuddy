<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\Setting;
use App\Models\TaxRate;
use App\Services\Loyalty\CoinRewardCalculator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

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

            // R1: coins earned for this line (informational). The order total is computed once, after the loop.
            $lineCoinsReward = CoinRewardCalculator::forAmount($lineSubTotal);

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
                'coins_earned' => $lineCoinsReward,
            ];
        }

        // R1: coins earned = master % of the cart product total BEFORE coupon, coin and Rs 20 discounts.
        // Shipping, GST and the COD fee are not part of the base.
        $totalCoinsEarned = CoinRewardCalculator::forAmount(round($subtotal, 2));

        // ══ COIN REDEMPTION LOGIC (R11: coins are applied FIRST) ══
        $coinDiscount = 0.0;
        $coinsRedeemed = 0;
        $discountPercent = (int) Setting::get(
            'loyalty_max_redemption_percent',
            Setting::get('loyalty_discount_percentage', 10),
        );
        $coinToCashRate = max(1, (int) Setting::get('loyalty_conversion_rate', 10));
        $isLoyaltyEnabled = (bool) Setting::get('loyalty_enabled', 1);

        // Max coins that can be redeemed: admin % of the order (products + tax), never above that amount.
        $maxAllowedCoinDiscount = (($subtotal + $taxTotal) * $discountPercent) / 100;
        $maxRedeemableCoins = (int) floor(max(0, min($maxAllowedCoinDiscount, $subtotal + $taxTotal)) * $coinToCashRate);
        $adminCoinCap = (int) Setting::get('loyalty_max_redeemable_coins', 0);
        if ($adminCoinCap > 0) {
            $maxRedeemableCoins = min($maxRedeemableCoins, $adminCoinCap);
        }

        if ($isLoyaltyEnabled && $coinsToRedeem > 0) {
            $coinsRedeemed = min($coinsToRedeem, $maxRedeemableCoins);
            $coinDiscount = $coinsRedeemed / $coinToCashRate;
        }

        // ══ COUPON LOGIC (R11: applied AFTER coins; percentage is on the full cart value) ══
        $discountTotal = 0.0;
        if ($coupon) {
            if ($coupon->discount_type === 'percentage') {
                // Percentage on the FULL displayed cart value (senior's decision), not on what is left after coins.
                $discountTotal = ($displaySubtotal * (float) $coupon->discount_value) / 100;
            } else {
                // Fixed amount: the full value, limited below to what is left to pay.
                $discountTotal = (float) $coupon->discount_value;
            }

            if ($coupon->max_discount_amount !== null) {
                $discountTotal = min($discountTotal, (float) $coupon->max_discount_amount);
            }

            // Safety cap: coins + coupon never exceed Subtotal + Tax, so the total cannot go negative
            // (this only bites when a coupon is larger than what is left after coins).
            $discountTotal = min($discountTotal, max(0.0, ($subtotal + $taxTotal) - $coinDiscount));
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

    /**
     * R9 + R10: payment-method adjustments, applied AFTER calculate() so the coupon/coin logic is untouched.
     *
     * - COD: adds the admin-set COD fee (non-taxable, earns no coins, never part of a refund).
     * - Online + customer chose COD first in this checkout + setting on: subtracts the online-switch discount
     *   (capped at the amount payable so the total never goes below 0). The COD fee is not charged.
     * - Method not given: nothing changes (existing callers keep today's totals).
     *
     * Always returns 'cod_fee' and 'online_switch_discount' (0 when not applicable).
     *
     * @param  array<string, mixed>  $pricing  result of calculate()
     * @return array<string, mixed>
     */
      public function applyPaymentMethodAdjustments(array $pricing, ?string $paymentMethod, bool $codWasSelected): array
    {
        $codFee = 0.0;
        $codFeeTax = 0.0;
        $switchDiscount = 0.0;
        $grandTotal = (float) ($pricing['grand_total'] ?? 0);

        // R8: COD is allowed up to and including the limit (order total before the COD fee). 0 = no limit.
        $codLimit = self::codLimitAmount();
        $pricing['cod_limit'] = $codLimit;
        $pricing['cod_limit_exceeded'] = $codLimit > 0 && round($grandTotal, 2) > $codLimit;

        if ($paymentMethod === 'cod') {
            if ($pricing['cod_limit_exceeded']) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Cash on Delivery is available only for orders up to ₹'.number_format($codLimit, 2).'. Please pay online.',
                ]);
            }
            $codFee = self::codFeeAmount();
            // GST-inclusive: the customer pays exactly the fee; the GST is the part of the fee that is tax.
            $gstPercent = self::codFeeGstPercent();
            $codFeeTax = round($codFee * $gstPercent / (100 + $gstPercent), 2);
            $grandTotal += $codFee;
        } elseif (in_array($paymentMethod, ['cashfree', 'razorpay'], true)
            && $codWasSelected
            && self::onlineSwitchDiscountEnabled()) {
            $switchDiscount = min(self::onlineSwitchDiscountAmount(), max(0.0, $grandTotal));
            $grandTotal -= $switchDiscount;
        }

        $pricing['cod_fee'] = round($codFee, 2);
        $pricing['cod_fee_tax'] = round($codFeeTax, 2);
        $pricing['online_switch_discount'] = round($switchDiscount, 2);
        $pricing['grand_total'] = max(0.0, round($grandTotal, 2));

        return $pricing;
    }

    /** GST % included in the COD fee (Setting `cod_fee_gst_percent`, default 0 = nothing shown, e.g. 18). */
    /** R8: max order total for COD (Setting `cod_limit`, default 5000, 0 = no limit). */
    public static function codLimitAmount(): float
    {
        $raw = Setting::get('cod_limit', 5000);

        return is_numeric($raw) ? max(0.0, (float) $raw) : 5000.0;
    }
    /** COD fee GST % (Setting `cod_fee_gst_percent`, default 0 = no GST until the admin sets it, e.g. 18). */
    public static function codFeeGstPercent(): float
    {
        $raw = Setting::get('cod_fee_gst_percent', 0);

        return is_numeric($raw) ? min(100.0, max(0.0, (float) $raw)) : 0.0;
    }
    /** R9: one admin-editable COD fee (Setting `cod_fee`, default 0). */
    public static function codFeeAmount(): float
    {
        $raw = Setting::get('cod_fee', 0);

        return is_numeric($raw) ? max(0.0, (float) $raw) : 0.0;
    }

    /** R10: on/off switch (Setting `online_switch_discount_enabled`, default off). */
    public static function onlineSwitchDiscountEnabled(): bool
    {
        return (string) Setting::get('online_switch_discount_enabled', '0') === '1';
    }

    /** R10: discount amount (Setting `online_switch_discount_amount`, default 20). */
    public static function onlineSwitchDiscountAmount(): float
    {
        $raw = Setting::get('online_switch_discount_amount', 20);

        return is_numeric($raw) ? max(0.0, (float) $raw) : 0.0;
    }
}
