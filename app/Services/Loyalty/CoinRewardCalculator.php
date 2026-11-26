<?php

namespace App\Services\Loyalty;

use App\Models\Setting;

/**
 * R1: master coin-earning percentage.
 *
 * Coins are a percentage of the cart's product total (pre-GST subtotal) BEFORE
 * coupon, coin and online-switch discounts. Shipping and the COD fee are never part of the base.
 *
 * The percentage is a VALUE percentage: coins = amount x percent / 100 x conversion rate.
 * Default 0.5 with 10 coins = Rs 1 gives 5 coins per Rs 100, i.e. today's effective rate.
 */
class CoinRewardCalculator
{
    public const EARN_PERCENT_KEY = 'loyalty_earn_percent';

    public const DEFAULT_EARN_PERCENT = 0.5;

    /** Value percentage (0 to 100) of the cart product total that is returned as coins. */
    public static function earnPercent(): float
    {
        $raw = Setting::get(self::EARN_PERCENT_KEY, self::DEFAULT_EARN_PERCENT);

        if (! is_numeric($raw)) {
            return self::DEFAULT_EARN_PERCENT;
        }

        return min(100.0, max(0.0, (float) $raw));
    }

    /** Coins that equal Rs 1 (same setting the redemption side uses). */
    public static function conversionRate(): int
    {
        return max(1, (int) Setting::get('loyalty_conversion_rate', 10));
    }

    /**
     * @param  float  $cartProductTotal  pre-GST product total before any discount; shipping and COD fee excluded
     */
    public static function forAmount(float $cartProductTotal): int
    {
        if ($cartProductTotal <= 0) {
            return 0;
        }

        $coins = $cartProductTotal * self::earnPercent() / 100 * self::conversionRate();

        // round() first so float noise (e.g. 49.9999999) cannot lose a whole coin.
        return (int) floor(round($coins, 6));
    }
}
