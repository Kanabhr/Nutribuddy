<?php

namespace App\Http\Controllers\Frontend\Account;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to Google's OAuth page.
     */
    public function redirect(Request $request)
    {
        if (Setting::get('google_login_enabled', '0') !== '1') {
            return redirect()->route('home')->with('error', 'Google login is currently disabled.');
        }

        if ($request->filled('redirect')) {
            $request->session()->put('url.intended', $this->safeRedirect($request->query('redirect')));
        }

        $this->configureGoogleDriver();

        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the callback from Google.
     */
    public function callback(Request $request)
    {
        if (Setting::get('google_login_enabled', '0') !== '1') {
            return redirect()->route('home')->with('error', 'Google login is currently disabled.');
        }

        $this->configureGoogleDriver();

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::error('Google OAuth callback failed', [
                'exception' => get_class($e),
            ]);

            return redirect()->route('home')->with('error', 'Google login failed. Please try again later.');
        }

        // Find or create user
        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            // Check if a user with the same email already exists — link Google to their account
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'google_avatar' => $googleUser->getAvatar(),
                ]);
            } else {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'google_avatar' => $googleUser->getAvatar(),
                    'password' => bcrypt(Str::random(32)),
                    'role' => 'customer',
                    'is_active' => true,
                ]);
            }
        }

        if (! $user->is_active) {
            return redirect()->route('home')->with('error', 'Your account has been deactivated. Please contact support.');
        }

        if ($user->role !== 'customer') {
            return redirect()->route('home')->with('error', 'Admin accounts cannot use Google login.');
        }

        Auth::guard('web')->login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->to($this->safeRedirect($request->session()->pull('url.intended')));
    }

    /**
     * Load Google credentials from DB into Socialite config at runtime.
     */
    private function configureGoogleDriver(): void
    {
        $clientId = Setting::get('google_client_id', config('services.google.client_id'));
        $clientSecret = Setting::get('google_client_secret', config('services.google.client_secret'));
        $redirectUrl = url('/auth/google/callback');

        config([
            'services.google.client_id' => $clientId,
            'services.google.client_secret' => $clientSecret,
            'services.google.redirect' => $redirectUrl,
        ]);

        // On Windows local dev, PHP lacks the CA bundle needed to verify Google's SSL cert.
        // Disable SSL verification only in local environment — NEVER in production.
        if (app()->environment('local')) {
            $guzzle = new Client(['verify' => false]);
            Socialite::driver('google')->setHttpClient($guzzle);
        }
    }

    private function safeRedirect(?string $redirectTo): string
    {
        if (! $redirectTo || str_contains($redirectTo, '\\')) {
            return route('userdashboard');
        }

        if (str_starts_with($redirectTo, '/') && ! str_starts_with($redirectTo, '//')) {
            return $redirectTo;
        }

        $scheme = parse_url($redirectTo, PHP_URL_SCHEME);
        if (URL::isValidUrl($redirectTo)
            && in_array(strtolower((string) $scheme), ['http', 'https'], true)
            && parse_url($redirectTo, PHP_URL_HOST) === request()->getHost()) {
            return $redirectTo;
        }

        return route('userdashboard');
    }
}
