<?php

namespace App\Http\Controllers\Admin\Access;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Access\AdminLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticationController extends Controller
{
    public function forgotPassword()
    {
        return view('frontend.auth.forgot-password');
    }

    public function signIn()
    {
        return view('frontend.auth.signin');
    }

    public function login(AdminLoginRequest $request)
    {
        $credentials = $request->validated();

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $admin = Auth::guard('admin')->user();
            if ($admin->role !== 'admin' || ! $admin->is_active) {
                Auth::guard('admin')->logout();

                // Clear any stale 2FA session keys from a previous admin.
                $this->clearTwoFactorSessionKeys($request);

                $request->session()->regenerate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => $admin->is_active
                        ? 'Unauthorized access. Only admins can login here.'
                        : 'This admin account has been deactivated.',
                ])->onlyInput('email');
            }

            // Clear any stale 2FA flags left by a previous admin in this browser
            // BEFORE regenerating so the new admin starts without a verified flag.
            $this->clearTwoFactorSessionKeys($request);

            $request->session()->regenerate();

            return redirect()->intended(route('admin.index'))
                ->with('success', 'Logged in successfully.');
        }

        // Clear stale 2FA keys on a failed login attempt too.
        $this->clearTwoFactorSessionKeys($request);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        // Clear all 2FA session keys so the next admin is not pre-verified.
        $this->clearTwoFactorSessionKeys($request);

        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('signin')
            ->with('success', 'Logged out successfully.');
    }

    /**
     * Remove all 2FA-related keys from the session.
     * Uses forget() (not invalidate()) so a customer session in the same
     * browser is not destroyed — admin and customer guards share one session.
     */
    private function clearTwoFactorSessionKeys(Request $request): void
    {
        $request->session()->forget([
            'admin_2fa_verified',
            'admin_2fa_last_ts',
            '2fa_pending_secret',
        ]);
    }

    public function signUp()
    {
        return view('frontend.auth.signup');
    }
}
