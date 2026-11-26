<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use ReflectionProperty;
use Tests\TestCase;

/** R8: COD limit (default 5000). Exactly at the limit is allowed; above is refused on the server. */
class CodLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearCache();
    }

    protected function tearDown(): void
    {
        $this->clearCache();
        parent::tearDown();
    }

    private function clearCache(): void
    {
        $p = new ReflectionProperty(Setting::class, 'cachedValues');
        $p->setAccessible(true);
        $p->setValue(null, []);
    }

    private function adjust(float $total, string $method): array
    {
        return app(PricingService::class)->applyPaymentMethodAdjustments(['grand_total' => $total], $method, false);
    }

    public function test_default_limit_is_5000(): void
    {
        $this->assertSame(5000.0, PricingService::codLimitAmount());
    }

    public function test_cod_exactly_at_the_limit_is_allowed(): void
    {
        $r = $this->adjust(5000.00, 'cod');
        $this->assertFalse($r['cod_limit_exceeded']);
    }

    public function test_cod_one_paisa_above_the_limit_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->adjust(5000.01, 'cod');
    }

    public function test_online_payment_above_the_limit_is_allowed_and_flagged_for_the_page(): void
    {
        $r = $this->adjust(9000, 'cashfree');
        $this->assertTrue($r['cod_limit_exceeded']);
        $this->assertSame(5000.0, $r['cod_limit']);
    }

    public function test_limit_is_checked_before_the_cod_fee_is_added(): void
    {
        Setting::set('cod_fee', 50, 'payment');
        $r = $this->adjust(5000, 'cod');
        $this->assertEquals(5050, $r['grand_total']);
    }

    public function test_limit_zero_means_no_limit_and_admin_value_is_used(): void
    {
        Setting::set('cod_limit', 0, 'payment');
        $this->assertFalse($this->adjust(99999, 'cod')['cod_limit_exceeded']);
        $this->clearCache();
        Setting::set('cod_limit', 1000, 'payment');
        $this->expectException(ValidationException::class);
        $this->adjust(1000.01, 'cod');
    }
}
