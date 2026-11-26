<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionProperty;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/** Admin forms for R1 (coin percentage) and R9/R10 (COD fee, online-switch discount). */
class CodAndLoyaltySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->withoutMiddleware(ValidateCsrfToken::class);
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

    private function adminWithPermission(string $permission): User
    {
        Permission::create(['name' => $permission, 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Role '.$permission, 'guard_name' => 'admin']);
        $role->givePermissionTo($permission);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        return $admin;
    }

    private function codUrl(): string
    {
        return route('admin.ecommerce.settings.payment-gateways.update');
    }

    // ------------------------------------------------------- COD (R9, R10)

    public function test_admin_can_save_cod_fee_and_online_switch_discount(): void
    {
        $admin = $this->adminWithPermission('manage administration');

        $this->actingAs($admin, 'admin')->post($this->codUrl(), [
            'gateway' => 'cod',
            'cod_enabled' => '1',
            'cod_fee' => '25.50',
            'online_switch_discount_enabled' => '1',
            'online_switch_discount_amount' => '15',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertEquals(25.5, (float) Setting::get('cod_fee'));
        $this->assertSame('1', (string) Setting::get('online_switch_discount_enabled'));
        $this->assertEquals(15, (float) Setting::get('online_switch_discount_amount'));
    }

    public function test_negative_or_non_numeric_amounts_are_rejected_and_nothing_is_saved(): void
    {
        $admin = $this->adminWithPermission('manage administration');
        Setting::set('cod_fee', 10, 'payment');

        $this->actingAs($admin, 'admin')->post($this->codUrl(), [
            'gateway' => 'cod',
            'cod_fee' => '-5',
            'online_switch_discount_amount' => 'abc',
        ])->assertSessionHasErrors(['cod_fee', 'online_switch_discount_amount']);

        $this->assertEquals(10, (float) Setting::get('cod_fee'));
    }

    public function test_bulk_update_without_the_new_fields_does_not_reset_them(): void
    {
        $admin = $this->adminWithPermission('manage administration');
        Setting::set('cod_fee', 25, 'payment');
        Setting::set('online_switch_discount_enabled', '1', 'payment');
        Setting::set('online_switch_discount_amount', 15, 'payment');

        // No `gateway` field = the bulk branch. The new fields are not posted.
        $this->actingAs($admin, 'admin')->post($this->codUrl(), [
            'cod_enabled' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertEquals(25, (float) Setting::get('cod_fee'));
        $this->assertSame('1', (string) Setting::get('online_switch_discount_enabled'));
        $this->assertEquals(15, (float) Setting::get('online_switch_discount_amount'));
    }

    public function test_razorpay_form_does_not_touch_the_cod_pricing_settings(): void
    {
        $admin = $this->adminWithPermission('manage administration');
        Setting::set('cod_fee', 25, 'payment');

        $this->actingAs($admin, 'admin')->post($this->codUrl(), [
            'gateway' => 'razorpay',
            'razorpay_env' => 'sandbox',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertEquals(25, (float) Setting::get('cod_fee'));
    }

    public function test_user_without_permission_cannot_change_cod_settings(): void
    {
        $admin = $this->adminWithPermission('view loyalty');

        $response = $this->actingAs($admin, 'admin')->post($this->codUrl(), [
            'gateway' => 'cod',
            'cod_fee' => '99',
        ]);

        $this->assertContains($response->status(), [302, 403]);
        $this->assertEquals(0, (float) Setting::get('cod_fee', 0));
    }

    public function test_payment_gateway_page_shows_the_new_fields(): void
    {
        $admin = $this->adminWithPermission('manage administration');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.ecommerce.settings.payment-gateways'))
            ->assertOk()
            ->assertSee('COD Fee')
            ->assertSee('Online Payment Discount After Choosing COD');
    }

    // ------------------------------------------------------------ Loyalty (R1)

    private function loyaltyPayload(array $extra = []): array
    {
        return array_merge([
            'loyalty_conversion_rate' => 10,
            'loyalty_discount_percentage' => 10,
            'loyalty_enabled' => 1,
        ], $extra);
    }

    public function test_admin_can_set_the_coin_earn_percentage(): void
    {
        $admin = $this->adminWithPermission('manage loyalty');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.ecommerce.loyalty.settings.update'), $this->loyaltyPayload(['loyalty_earn_percent' => '1.5']))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertEquals(1.5, (float) Setting::get('loyalty_earn_percent'));
    }

    public function test_earn_percentage_must_be_between_0_and_100(): void
    {
        $admin = $this->adminWithPermission('manage loyalty');
        Setting::set('loyalty_earn_percent', 0.5);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.ecommerce.loyalty.settings.update'), $this->loyaltyPayload(['loyalty_earn_percent' => '150']))
            ->assertSessionHasErrors('loyalty_earn_percent');
        $this->actingAs($admin, 'admin')
            ->post(route('admin.ecommerce.loyalty.settings.update'), $this->loyaltyPayload(['loyalty_earn_percent' => '-1']))
            ->assertSessionHasErrors('loyalty_earn_percent');

        $this->assertEquals(0.5, (float) Setting::get('loyalty_earn_percent'));
    }

    public function test_existing_loyalty_form_without_the_new_field_still_saves_and_keeps_the_percentage(): void
    {
        $admin = $this->adminWithPermission('manage loyalty');
        Setting::set('loyalty_earn_percent', 2);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.ecommerce.loyalty.settings.update'), $this->loyaltyPayload())
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertEquals(2, (float) Setting::get('loyalty_earn_percent'));
        $this->assertEquals(10, (int) Setting::get('loyalty_conversion_rate'));
    }

    public function test_loyalty_page_shows_the_earn_percentage_field(): void
    {
        $admin = $this->adminWithPermission('manage loyalty');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.ecommerce.loyalty.settings'))
            ->assertOk()
            ->assertSee('Coins Earned (% of cart value)')
            ->assertSee('name="loyalty_earn_percent"', false);
    }
}
