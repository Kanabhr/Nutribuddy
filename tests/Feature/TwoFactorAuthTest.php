<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected TwoFactorService $twoFactor;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('view dashboard', 'admin');
        $role = Role::findOrCreate('Super Admin', 'admin');
        $role->givePermissionTo('view dashboard');

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->admin->assignRole($role);
        $this->twoFactor = app(TwoFactorService::class);
    }

    // ── TwoFactorService unit tests ───────────────────────────────────────────

    public function test_generate_secret_stores_encrypted_secret(): void
    {
        $plain = $this->twoFactor->generateSecret($this->admin);

        $this->admin->refresh();
        $this->assertNotNull($this->admin->two_factor_secret);
        $this->assertNotSame($plain, $this->admin->two_factor_secret); // stored encrypted
        $this->assertNull($this->admin->two_factor_confirmed_at);
    }

    public function test_confirm_enables_2fa_with_valid_totp_code(): void
    {
        $plain = $this->twoFactor->generateSecret($this->admin);
        $google = new Google2FA;
        $code = $google->getCurrentOtp($plain);

        $result = $this->twoFactor->confirm($this->admin, $code);

        $this->assertTrue($result);
        $this->admin->refresh();
        $this->assertNotNull($this->admin->two_factor_confirmed_at);
        $this->assertNotNull($this->admin->two_factor_recovery_codes);
    }

    public function test_confirm_fails_with_wrong_code(): void
    {
        $this->twoFactor->generateSecret($this->admin);

        $result = $this->twoFactor->confirm($this->admin, '000000');

        $this->assertFalse($result);
        $this->admin->refresh();
        $this->assertNull($this->admin->two_factor_confirmed_at);
    }

    public function test_disable_clears_all_2fa_columns(): void
    {
        $plain = $this->twoFactor->generateSecret($this->admin);
        $google = new Google2FA;
        $this->twoFactor->confirm($this->admin, $google->getCurrentOtp($plain));

        $this->twoFactor->disable($this->admin);
        $this->admin->refresh();

        $this->assertNull($this->admin->two_factor_secret);
        $this->assertNull($this->admin->two_factor_recovery_codes);
        $this->assertNull($this->admin->two_factor_confirmed_at);
        $this->assertNull($this->admin->two_factor_last_used_step);
    }

    public function test_verify_code_returns_true_for_current_otp(): void
    {
        $plain = $this->twoFactor->generateSecret($this->admin);
        $google = new Google2FA;
        $code = $google->getCurrentOtp($plain);

        $result = $this->twoFactor->verifyCode($this->admin, $code);
        $this->assertNotFalse($result); // returns true or int on success
    }

    public function test_use_recovery_code_consumes_it_once(): void
    {
        $plain = $this->twoFactor->generateSecret($this->admin);
        $google = new Google2FA;
        $this->twoFactor->confirm($this->admin, $google->getCurrentOtp($plain));

        // Inject a known recovery code hash.
        $knownCode = 'abcde-fghij';
        $hashed = json_encode([Hash::make('abcdefghij')]);
        $this->admin->forceFill(['two_factor_recovery_codes' => $hashed])->save();

        $this->assertTrue($this->twoFactor->useRecoveryCode($this->admin, $knownCode));

        // Second use must fail.
        $this->assertFalse($this->twoFactor->useRecoveryCode($this->admin, $knownCode));
    }

    public function test_two_factor_secret_hidden_from_serialization(): void
    {
        $this->twoFactor->generateSecret($this->admin);
        $json = $this->admin->toArray();

        $this->assertArrayNotHasKey('two_factor_secret', $json);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $json);
    }

    // ── HTTP flow tests ───────────────────────────────────────────────────────

    public function test_setup_page_loads_for_authenticated_admin(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->withSession(['admin_2fa_verified' => $this->admin->id])
            ->get(route('admin.2fa.setup'))
            ->assertOk()
            ->assertSee('Two-Factor Authentication');
    }

    public function test_enable_route_generates_pending_secret(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->withSession(['admin_2fa_verified' => $this->admin->id])
            ->post(route('admin.2fa.enable'))
            ->assertRedirect(route('admin.2fa.setup'));

        $this->admin->refresh();
        $this->assertNotNull($this->admin->two_factor_secret);
        $this->assertNull($this->admin->two_factor_confirmed_at); // not confirmed yet
    }

    public function test_confirm_route_activates_2fa(): void
    {
        $plain = $this->twoFactor->generateSecret($this->admin);
        $google = new Google2FA;
        $code = $google->getCurrentOtp($plain);

        $this->actingAs($this->admin, 'admin')
            ->withSession(['admin_2fa_verified' => $this->admin->id, '2fa_pending_secret' => $plain])
            ->post(route('admin.2fa.confirm'), ['code' => $code])
            ->assertRedirect(route('admin.2fa.setup'));

        $this->admin->refresh();
        $this->assertNotNull($this->admin->two_factor_confirmed_at);
    }

    public function test_challenge_redirects_2fa_enabled_admin_who_has_not_verified(): void
    {
        // Enable 2FA
        $plain = $this->twoFactor->generateSecret($this->admin);
        $google = new Google2FA;
        $this->twoFactor->confirm($this->admin, $google->getCurrentOtp($plain));

        // Not 2FA-verified in session → should redirect to challenge
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.index'))
            ->assertRedirect(route('admin.2fa.challenge'));
    }

    public function test_challenge_page_accessible_while_pending_verification(): void
    {
        $plain = $this->twoFactor->generateSecret($this->admin);
        $google = new Google2FA;
        $this->twoFactor->confirm($this->admin, $google->getCurrentOtp($plain));

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.2fa.challenge'))
            ->assertOk()
            ->assertSee('Two-Factor Authentication');
    }

    public function test_verify_route_marks_session_verified_on_valid_code(): void
    {
        $plain = $this->twoFactor->generateSecret($this->admin);
        $google = new Google2FA;
        $this->twoFactor->confirm($this->admin, $google->getCurrentOtp($plain));

        $code = $google->getCurrentOtp($plain);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.2fa.verify'), ['code' => $code]);

        $response->assertRedirect(route('admin.index'));
        $this->assertSame($this->admin->id, $response->getSession()->get('admin_2fa_verified'));
    }

    public function test_verify_route_rejects_invalid_code(): void
    {
        $plain = $this->twoFactor->generateSecret($this->admin);
        $google = new Google2FA;
        $this->twoFactor->confirm($this->admin, $google->getCurrentOtp($plain));

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.2fa.verify'), ['code' => '000000'])
            ->assertRedirect()
            ->assertSessionHasErrors('code');
    }

    public function test_admin_without_2fa_enabled_passes_straight_through(): void
    {
        // No 2FA configured → RequireAdminTwoFactor must pass through.
        $this->actingAs($this->admin, 'admin')
            ->withSession(['admin_2fa_verified' => false])
            ->get(route('admin.index'))
            ->assertOk();
    }

    public function test_disable_route_requires_correct_password(): void
    {
        $plain = $this->twoFactor->generateSecret($this->admin);
        $google = new Google2FA;
        $this->twoFactor->confirm($this->admin, $google->getCurrentOtp($plain));

        $this->actingAs($this->admin, 'admin')
            ->withSession(['admin_2fa_verified' => $this->admin->id])
            ->post(route('admin.2fa.disable'), ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');
    }

    public function test_replay_blocked_by_old_timestamp(): void
    {
        $plain = $this->twoFactor->generateSecret($this->admin);
        $google = new Google2FA;
        $code = $google->getCurrentOtp($plain);

        // First verify: verifyCode reads the stored timestep from the user row (null initially).
        $first = $this->twoFactor->verifyCode($this->admin, $code);
        $this->assertNotFalse($first);

        // The timestep is now persisted on the user row.
        $this->admin->refresh();
        $this->assertNotNull($this->admin->two_factor_last_used_step);

        // Second verify with the same code must be blocked by the stored timestep.
        $second = $this->twoFactor->verifyCode($this->admin, $code);
        $this->assertFalse($second);
    }

    public function test_verify_route_regenerates_session(): void
    {
        $plain = $this->twoFactor->generateSecret($this->admin);
        $google = new Google2FA;
        $this->twoFactor->confirm($this->admin, $google->getCurrentOtp($plain));

        $code = $google->getCurrentOtp($plain);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.2fa.verify'), ['code' => $code]);

        // After session regeneration the 2fa_verified flag must be bound to the admin id.
        $response->assertRedirect(route('admin.index'));
        $this->assertSame($this->admin->id, $response->getSession()->get('admin_2fa_verified'));
    }

    // ── Session-survival fix tests ────────────────────────────────────────────

    public function test_admin_a_logout_then_admin_b_login_is_challenged(): void
    {
        // Set up admin A with 2FA enabled.
        $plainA = $this->twoFactor->generateSecret($this->admin);
        $google = new Google2FA;
        $this->twoFactor->confirm($this->admin, $google->getCurrentOtp($plainA));

        // Admin B with 2FA enabled.
        $adminB = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $adminB->assignRole(Role::findOrCreate('Super Admin', 'admin'));
        $plainB = $this->twoFactor->generateSecret($adminB);
        $this->twoFactor->confirm($adminB, $google->getCurrentOtp($plainB));

        // Admin A verifies the challenge.
        $this->withSession(['admin_2fa_verified' => $this->admin->id])
            ->actingAs($this->admin, 'admin')
            ->get(route('admin.index'))
            ->assertOk();

        // Admin A logs out.
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.logout'))
            ->assertRedirect(route('signin'));

        // Admin B now accesses the panel — must be challenged, not pre-verified.
        $this->actingAs($adminB, 'admin')
            ->get(route('admin.index'))
            ->assertRedirect(route('admin.2fa.challenge'));
    }

    public function test_same_admin_logout_then_login_is_challenged_again(): void
    {
        $plain = $this->twoFactor->generateSecret($this->admin);
        $google = new Google2FA;
        $this->twoFactor->confirm($this->admin, $google->getCurrentOtp($plain));

        // Verify the challenge.
        $this->withSession(['admin_2fa_verified' => $this->admin->id])
            ->actingAs($this->admin, 'admin')
            ->get(route('admin.index'))
            ->assertOk();

        // Log out.
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.logout'))
            ->assertRedirect(route('signin'));

        // Log back in as the same admin — must be challenged again.
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.index'))
            ->assertRedirect(route('admin.2fa.challenge'));
    }

    public function test_session_flag_holding_different_admin_id_is_challenged(): void
    {
        $plain = $this->twoFactor->generateSecret($this->admin);
        $google = new Google2FA;
        $this->twoFactor->confirm($this->admin, $google->getCurrentOtp($plain));

        // Another admin's id in the session flag.
        $otherAdminId = User::factory()->create(['role' => 'admin'])->id;

        $this->actingAs($this->admin, 'admin')
            ->withSession(['admin_2fa_verified' => $otherAdminId])
            ->get(route('admin.index'))
            ->assertRedirect(route('admin.2fa.challenge'));
    }

    public function test_same_valid_code_rejected_from_second_session(): void
    {
        $plain = $this->twoFactor->generateSecret($this->admin);
        $google = new Google2FA;
        $this->twoFactor->confirm($this->admin, $google->getCurrentOtp($plain));

        $code = $google->getCurrentOtp($plain);

        // Session 1: verify the code — succeeds, stores timestep on user row.
        $first = $this->twoFactor->verifyCode($this->admin, $code);
        $this->assertNotFalse($first);

        // Session 2: the same code must be rejected because the timestep is on the DB row.
        $second = $this->twoFactor->verifyCode($this->admin, $code);
        $this->assertFalse($second);
    }
}
