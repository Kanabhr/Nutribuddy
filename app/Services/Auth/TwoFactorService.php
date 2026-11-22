<?php

namespace App\Services\Auth;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Handles all TOTP 2FA logic for admin users:
 *   - generate / enable / disable
 *   - TOTP code verification (with 1-window drift tolerance)
 *   - recovery code generation, hashing, and consumption
 *
 * The secret is AES-256-CBC encrypted via Laravel's Crypt facade before storage.
 * Recovery codes are bcrypt-hashed — only the plaintext list shown once at setup.
 */
class TwoFactorService
{
    public function __construct(private readonly Google2FA $google2fa) {}

    /** Generate a new secret and persist it (unconfirmed). Returns the plaintext secret. */
    public function generateSecret(User $user): string
    {
        $secret = $this->google2fa->generateSecretKey();

        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return $secret;
    }

    /** Confirm and activate 2FA after the user verifies the first TOTP code. */
    public function confirm(User $user, string $code): bool
    {
        if (empty($user->two_factor_secret)) {
            return false;
        }

        try {
            $secret = Crypt::decryptString($user->two_factor_secret);
        } catch (DecryptException) {
            return false;
        }

        // Verify without persisting the timestep — this is a setup step, not a
        // login challenge. The first real challenge must start fresh.
        if (! $this->google2fa->verifyKey($secret, $code, 1)) {
            return false;
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $this->hashRecoveryCodes($this->generateRecoveryCodes()),
        ])->save();

        return true;
    }

    /** Disable 2FA and clear all related columns. */
    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_used_step' => null,
        ])->save();
    }

    /** True if the user has completed 2FA setup. */
    public function isEnabled(User $user): bool
    {
        return $user->two_factor_confirmed_at !== null;
    }

    /** Verify a TOTP code (allows 1-window drift). Blocks replay across sessions via the user row. */
    public function verifyCode(User $user, string $code): bool|int
    {
        if (empty($user->two_factor_secret)) {
            return false;
        }

        try {
            $secret = Crypt::decryptString($user->two_factor_secret);
        } catch (DecryptException) {
            return false;
        }

        // Read the last-used timestep from the user row so replay is blocked
        // even from a new session or a different browser.
        $oldTimestamp = $user->two_factor_last_used_step;

        // verifyKeyNewer returns the used timestep (int) when oldTimestamp is
        // provided, true when oldTimestamp is null, false on mismatch.
        $result = $this->google2fa->verifyKeyNewer($secret, $code, $oldTimestamp, 1);

        if ($result === false) {
            return false;
        }

        // Persist the timestep that was accepted so the same code cannot be reused.
        $usedStep = is_int($result) ? $result : $this->google2fa->getTimestamp();
        $user->forceFill(['two_factor_last_used_step' => $usedStep])->save();

        return $result;
    }

    /**
     * Attempt to consume a one-time recovery code.
     * Returns true and removes the code if it matches; false otherwise.
     */
    public function useRecoveryCode(User $user, string $code): bool
    {
        $stored = json_decode((string) $user->two_factor_recovery_codes, true);
        if (! is_array($stored)) {
            return false;
        }

        $code = str_replace('-', '', Str::lower(trim($code)));

        foreach ($stored as $index => $hashed) {
            if (Hash::check($code, $hashed)) {
                unset($stored[$index]);
                $user->forceFill([
                    'two_factor_recovery_codes' => json_encode(array_values($stored)),
                ])->save();

                return true;
            }
        }

        return false;
    }

    /** Return the QR-code SVG for the setup page. */
    public function qrCodeSvg(User $user, string $plaintextSecret): string
    {
        $url = $this->google2fa->getQRCodeUrl(
            config('app.name', 'NutriBuddy'),
            $user->email,
            $plaintextSecret,
        );

        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(192),
                new SvgImageBackEnd,
            )
        );

        return $writer->writeString($url);
    }

    /** Generate 8 human-readable recovery codes. */
    public function generateRecoveryCodes(): array
    {
        return array_map(
            fn () => strtolower(Str::random(5).'-'.Str::random(5)),
            range(1, 8)
        );
    }

    /** bcrypt-hash each recovery code for storage. */
    private function hashRecoveryCodes(array $codes): string
    {
        return json_encode(array_map(fn (string $c) => Hash::make(
            str_replace('-', '', $c)
        ), $codes));
    }
}
