<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionProperty;
use Tests\TestCase;

/** GST on the COD fee: fee is GST-INCLUSIVE (fee 40 at 18% = customer pays 40, GST part 6.10). Default 0 shows nothing. */
class CodFeeGstTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $p = new ReflectionProperty(Setting::class, 'cachedValues');
        $p->setAccessible(true);
        $p->setValue(null, []);
    }

    private function adjust(string $method, float $total = 1000): array
    {
        return app(PricingService::class)->applyPaymentMethodAdjustments(['grand_total' => $total], $method, false);
    }

    public function test_default_gst_percent_is_zero_so_nothing_changes(): void
    {
        Setting::set('cod_fee', 40, 'payment');
        $r = $this->adjust('cod');
        $this->assertSame(0.0, $r['cod_fee_tax']);
        $this->assertEquals(1040, $r['grand_total']);
    }

    public function test_fee_40_is_gst_inclusive_customer_pays_exactly_40(): void
    {
        Setting::set('cod_fee', 40, 'payment');
        Setting::set('cod_fee_gst_percent', 18, 'payment');
        $r = $this->adjust('cod');
        $this->assertEquals(40.0, $r['cod_fee']);
        $this->assertEquals(6.10, $r['cod_fee_tax']);   // 40 x 18 / 118
        $this->assertEquals(1040, $r['grand_total']);   // nothing added on top
    }

    public function test_online_payment_has_no_fee_and_no_fee_tax(): void
    {
        Setting::set('cod_fee', 40, 'payment');
        Setting::set('cod_fee_gst_percent', 18, 'payment');
        $r = $this->adjust('cashfree');
        $this->assertSame(0.0, $r['cod_fee_tax']);
        $this->assertEquals(1000, $r['grand_total']);
    }

    public function test_invalid_or_out_of_range_percent_is_safe(): void
    {
        Setting::set('cod_fee_gst_percent', 'abc', 'payment');
        $this->assertSame(0.0, PricingService::codFeeGstPercent());
        $p = new ReflectionProperty(Setting::class, 'cachedValues');
        $p->setAccessible(true);
        $p->setValue(null, []);
        Setting::set('cod_fee_gst_percent', 500, 'payment');
        $this->assertSame(100.0, PricingService::codFeeGstPercent());
    }

    public function test_cod_limit_is_checked_before_fee_and_its_gst(): void
    {
        Setting::set('cod_fee', 40, 'payment');
        Setting::set('cod_fee_gst_percent', 18, 'payment');
        $r = $this->adjust('cod', 5000);
        $this->assertFalse($r['cod_limit_exceeded']);
        $this->assertEquals(5040, $r['grand_total']);
    }
}
