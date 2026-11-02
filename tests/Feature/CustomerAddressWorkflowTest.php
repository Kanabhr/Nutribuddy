<?php

namespace Tests\Feature;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAddressWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_setting_a_default_address_clears_the_previous_default_for_both_types(): void
    {
        $user = User::factory()->create();
        $previous = $this->makeAddress($user, 'Old address', true);
        $selected = $this->makeAddress($user, 'New address', false);

        $this->actingAs($user)
            ->patchJson(route('user.addresses.set-default', $selected))
            ->assertOk()
            ->assertExactJson(['message' => 'Default address updated.']);

        $previous->refresh();
        $selected->refresh();
        $this->assertFalse($previous->is_default_shipping);
        $this->assertFalse($previous->is_default_billing);
        $this->assertTrue($selected->is_default_shipping);
        $this->assertTrue($selected->is_default_billing);
    }

    private function makeAddress(User $user, string $line, bool $default): CustomerAddress
    {
        return CustomerAddress::create([
            'user_id' => $user->id,
            'full_name' => 'Test Customer',
            'phone' => '9876543210',
            'address_line_1' => $line,
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'postal_code' => '411001',
            'is_default_shipping' => $default,
            'is_default_billing' => $default,
        ]);
    }
}
