<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\Loyalty\CoinRewardCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionProperty;
use Tests\TestCase;

/** R1: master coin percentage on the cart product total. */
class CoinRewardCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearSettingCache();
    }

    protected function tearDown(): void
    {
        $this->clearSettingCache();
        parent::tearDown();
    }

    private function clearSettingCache(): void
    {
        $property = new ReflectionProperty(Setting::class, 'cachedValues');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }

    public function test_default_rate_equals_todays_five_coins_per_hundred_rupees(): void
    {
        $this->assertSame(0.5, CoinRewardCalculator::earnPercent());
        $this->assertSame(5, CoinRewardCalculator::forAmount(100.0));
        $this->assertSame(50, CoinRewardCalculator::forAmount(1000.0));
    }

    public function test_coins_are_rounded_down_to_whole_coins(): void
    {
        $this->assertSame(4, CoinRewardCalculator::forAmount(99.0));   // 4.95
        $this->assertSame(0, CoinRewardCalculator::forAmount(19.0));    // 0.95
    }

    public function test_admin_percentage_changes_the_result(): void
    {
        Setting::set('loyalty_earn_percent', 5);

        $this->assertSame(500, CoinRewardCalculator::forAmount(1000.0));
    }

    public function test_conversion_rate_changes_the_result(): void
    {
        Setting::set('loyalty_conversion_rate', 20);

        $this->assertSame(100, CoinRewardCalculator::forAmount(1000.0));
    }

    public function test_zero_percent_and_non_positive_amounts_earn_nothing(): void
    {
        Setting::set('loyalty_earn_percent', 0);
        $this->assertSame(0, CoinRewardCalculator::forAmount(1000.0));

        Setting::set('loyalty_earn_percent', 5);
        $this->assertSame(0, CoinRewardCalculator::forAmount(0.0));
        $this->assertSame(0, CoinRewardCalculator::forAmount(-50.0));
    }

    public function test_invalid_or_out_of_range_settings_are_handled_safely(): void
    {
        Setting::set('loyalty_earn_percent', 'abc');
        $this->assertSame(0.5, CoinRewardCalculator::earnPercent());

        Setting::set('loyalty_earn_percent', 250);
        $this->assertSame(100.0, CoinRewardCalculator::earnPercent());

        Setting::set('loyalty_earn_percent', -3);
        $this->assertSame(0.0, CoinRewardCalculator::earnPercent());
    }

    public function test_float_noise_does_not_lose_a_coin(): void
    {
        Setting::set('loyalty_earn_percent', 0.7);

        // 1000 x 0.7% x 10 coins/Rs = 70 coins exactly, even though 0.7 is not exact in floating point.
        $this->assertSame(70, CoinRewardCalculator::forAmount(1000.0));
    }
}
