<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * Handles the 2FA setup/enable/disable flow and the per-login TOTP challenge.
 *
 * Routes:
 *   GET  admin/2fa/setup      — show QR + secret (auth required)
 *   POST admin/2fa/enable     — generate secret
 *   POST admin/2fa/confirm    — confirm first code and activate
 *   POST admin/2fa/disable    — disable 2FA
 *   GET  admin/2fa/challenge  — show TOTP challenge form (after password login)
 *   POST admin/2fa/verify     — verify TOTP or recovery code
 */
class TwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactor) {}

    // ── Setup ──────────────────────────────────────────────────────────────────

    public function setup(): View
    {
        $admin = auth('admin')->user();
        $pendingSecret = session('2fa_pending_secret');

        $qrSvg = null;
        if ($pendingSecret) {
            $qrSvg = $this->twoFactor->qrCodeSvg($admin, $pendingSecret);
        }

        return view('admin.auth.two-factor.setup', [
            'admin' => $admin,
            'enabled' => $this->twoFactor->isEnabled($admin),
            'pendingSecret' => $pendingSecret,
            'qrSvg' => $qrSvg,
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $secret = $this->twoFactor->generateSecret(auth('admin')->user());
        $request->session()->put('2fa_pending_secret', $secret);

        return redirect()->route('admin.2fa.setup')
            ->with('success', 'Scan the QR code with your authenticator app, then enter the code to confirm.');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'digits:6']]);

        $admin = auth('admin')->user();
        $throttleKey = '2fa-confirm:'.$admin->id.':'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors(['code' => "Too many attempts. Please wait {$seconds} seconds."]);
        }

        if (! $this->twoFactor->confirm($admin, $request->input('code'))) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors(['code' => 'Invalid code. Please try again.']);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->forget('2fa_pending_secret');
        $recoveryCodes = $this->freshRecoveryCodes($admin);

        return redirect()->route('admin.2fa.setup')
            ->with('success', '2FA enabled successfully.')
            ->with('recovery_codes', $recoveryCodes);
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password:admin']]);

        $this->twoFactor->disable(auth('admin')->user());
        $request->session()->forget(['admin_2fa_verified', 'admin_2fa_last_ts', '2fa_pending_secret']);

        return redirect()->route('admin.2fa.setup')
            ->with('success', '2FA has been disabled.');
    }

    // ── Challenge (per login) ──────────────────────────────────────────────────

    public function challenge(): View|RedirectResponse
    {
        if (! auth('admin')->check()) {
            return redirect()->route('signin');
        }

        if (request()->session()->get('admin_2fa_verified')) {
            return redirect()->route('admin.index');
        }

        return view('admin.auth.two-factor.challenge');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $admin = auth('admin')->user();
        $throttleKey = '2fa:'.$admin->id.':'.$request->ip();

        // Rate-limit: 5 attempts per minute.
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors(['code' => "Too many attempts. Please wait {$seconds} seconds."]);
        }

        $code = trim($request->input('code'));
        // verifyCode reads the last-used timestep from the user row (not the session)
        // so replay is blocked even from a new session.
        $result = $this->twoFactor->verifyCode($admin, $code);
        $verified = ($result !== false) || $this->twoFactor->useRecoveryCode($admin, $code);

        if (! $verified) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors(['code' => 'Invalid code. If you have lost access, use a recovery code.']);
        }

        RateLimiter::clear($throttleKey);
        // Regenerate session ID after a successful second-factor to prevent session fixation.
        $request->session()->regenerate();
        // Bind the verified flag to this admin's id so it cannot be reused by another admin.
        $request->session()->put('admin_2fa_verified', $admin->id);

        return redirect()->intended(route('admin.index'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Re-generates and returns plaintext recovery codes for the confirmation view.
     * The hashed versions are already stored by confirm().
     */
    private function freshRecoveryCodes(mixed $admin): array
    {
        // Decode the stored JSON to get hashed codes count, then generate matching plaintext.
        // Since we can't reverse hashes, we regenerate a fresh set and re-store.
        $plain = $this->twoFactor->generateRecoveryCodes();
        $admin->forceFill([
            'two_factor_recovery_codes' => json_encode(
                array_map(fn ($c) => Hash::make(
                    str_replace('-', '', $c)
                ), $plain)
            ),
        ])->save();

        return $plain;
    }
}
