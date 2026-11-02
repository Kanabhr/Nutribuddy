<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class FrontendAuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_otp_is_stored_as_a_hash_and_omitted_from_json(): void
    {
        $response = $this->postJson(route('frontend.sendOtp'), [
            'phone' => '9876543210',
        ])->assertOk();

        $payload = $response->json();

        $this->assertArrayNotHasKey('otp', $payload);
        $this->assertTrue(Hash::check('123456', User::where('phone', '9876543210')->value('otp')));
    }

    public function test_production_otp_is_random_delivered_and_verified(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->app->detectEnvironment(fn () => 'production');
        config([
            'services.aisensy.enabled' => true,
            'services.aisensy.api_key' => 'test-api-key',
            'services.aisensy.otp_campaign' => 'customer-otp',
        ]);
        Http::fake();
        $sentOtp = null;

        $this->postJson(route('frontend.sendOtp'), ['phone' => '9876543210'])
            ->assertOk()
            ->assertJsonMissingPath('otp');

        Http::assertSent(function (HttpRequest $request) use (&$sentOtp): bool {
            $sentOtp = data_get($request->data(), 'templateParams.0');

            return $request->url() === config('services.aisensy.endpoint')
                && data_get($request->data(), 'campaignName') === 'customer-otp'
                && is_string($sentOtp)
                && preg_match('/^\d{6}$/', $sentOtp) === 1;
        });

        $user = User::where('phone', '9876543210')->firstOrFail();
        $this->assertTrue(Hash::check($sentOtp, $user->otp));

        $this->postJson(route('frontend.verifyOtp'), [
            'phone' => $user->phone,
            'otp' => $sentOtp,
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_verify_otp_rejects_external_redirects(): void
    {
        $user = User::factory()->create([
            'phone' => '9876543211',
            'role' => 'customer',
            'otp' => Hash::make('654321'),
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        $this->postJson(route('frontend.verifyOtp'), [
            'phone' => $user->phone,
            'otp' => '654321',
            'redirect_to' => 'https://example.com/phishing',
        ])
            ->assertOk()
            ->assertJsonPath('redirect', route('userdashboard'));
    }

    public function test_customer_logout_rotates_the_session_and_preserves_admin_guard(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($customer, 'web')->actingAs($admin, 'admin');
        $sessionId = session()->getId();

        $this->post(route('frontend.logout'))
            ->assertRedirect(route('home'));

        $this->assertNotSame($sessionId, session()->getId());
        $this->assertGuest('web');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->get(route('admin.index'))->assertForbidden();
    }

    public function test_admin_logout_rotates_the_session_and_preserves_web_guard(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($customer, 'web')->actingAs($admin, 'admin');
        $sessionId = session()->getId();

        $this->post(route('admin.logout'))->assertRedirect(route('signin'));

        $this->assertNotSame($sessionId, session()->getId());
        $this->assertGuest('admin');
        $this->assertAuthenticatedAs($customer, 'web');
    }

    public function test_user_serialization_never_exposes_otp_or_expiration(): void
    {
        $user = User::factory()->create([
            'otp' => Hash::make('123456'),
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        $this->assertArrayNotHasKey('otp', $user->toArray());
        $this->assertArrayNotHasKey('otp_expires_at', $user->toArray());
    }

    public function test_google_login_rejects_external_return_urls(): void
    {
        Setting::set('google_login_enabled', '1', 'auth');
        $provider = \Mockery::mock();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('/oauth-started'));
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

        $this->get(route('frontend.google.redirect', ['redirect' => 'https://example.net/phishing']))
            ->assertRedirect('/oauth-started')
            ->assertSessionHas('url.intended', route('userdashboard'));
    }

    public function test_google_oauth_errors_are_logged_without_request_parameters_or_returned_details(): void
    {
        Setting::set('google_login_enabled', '1', 'auth');
        Log::spy();
        $provider = \Mockery::mock();
        $provider->shouldReceive('user')->once()->andThrow(new \RuntimeException('provider-secret-detail'));
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

        $this->get('/auth/google/callback?code=private-code&state=private-state')
            ->assertRedirect(route('home'))
            ->assertSessionHas('error', 'Google login failed. Please try again later.')
            ->assertSessionMissing('url.intended');

        Log::shouldHaveReceived('error')->once()->with('Google OAuth callback failed', [
            'exception' => \RuntimeException::class,
        ]);
    }

    public function test_debug_authorization_page_is_not_registered_outside_local_environment(): void
    {
        $this->get('/debug/authorization')->assertNotFound();
    }
}
