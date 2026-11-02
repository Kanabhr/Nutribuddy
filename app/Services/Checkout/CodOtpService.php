<?php

namespace App\Services\Checkout;

use App\Models\CustomerAddress;
use App\Models\Setting;
use App\Models\User;
use App\Services\Notifications\AiSensyService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class CodOtpService
{
    public const CACHE_PREFIX = 'cod_otp:';
    public const ATTEMPTS_PREFIX = 'cod_otp_attempts:';
    public const TTL_SECONDS = 600; // 10 minutes
    public const MAX_ATTEMPTS = 5;
    public const RATE_LIMIT_MAX = 3;
    public const RATE_LIMIT_DECAY = 300; // 5 minutes

    public function __construct(
        private readonly AiSensyService $aiSensyService
    ) {}

    /**
     * Check if COD OTP verification is active in settings.
     */
    public function isEnabled(): bool
    {
        return Setting::get('cod_otp_enabled', '1') === '1';
    }

    /**
     * Generate and send a 6-digit OTP to the customer's delivery phone number.
     *
     * @throws ValidationException
     */
    public function sendOtp(User $user, CustomerAddress $address): array
    {
        $rateLimitKey = 'cod-otp-send:' . $user->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, self::RATE_LIMIT_MAX)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            throw ValidationException::withMessages([
                'cod_otp' => ["Too many OTP requests. Please wait {$seconds} seconds before requesting a new code."],
            ]);
        }

        $phone = $address->phone ?: $user->phone;
        $cleanPhone = preg_replace('/\D+/', '', (string) $phone);

        if (empty($cleanPhone) || strlen($cleanPhone) < 10) {
            throw ValidationException::withMessages([
                'address_id' => ['The selected delivery address does not have a valid 10-digit phone number.'],
            ]);
        }

        $isDevelopment = app()->environment('local', 'testing') ||
            str_starts_with((string) config('app.url'), 'http://127.0.0.1') ||
            str_starts_with((string) config('app.url'), 'http://localhost');

        $usesTestOtp = (bool) config('services.aisensy.use_test_otp', false) ||
            ($isDevelopment && ! (bool) config('services.aisensy.enabled', true));

        $otp = $usesTestOtp ? '123456' : (string) random_int(100000, 999999);

        // Store hashed OTP in cache
        $cacheKey = self::CACHE_PREFIX . $user->id;
        $attemptsKey = self::ATTEMPTS_PREFIX . $user->id;

        Cache::put($cacheKey, [
            'hash' => Hash::make($otp),
            'phone' => $cleanPhone,
            'address_id' => $address->id,
            'created_at' => now()->timestamp,
        ], self::TTL_SECONDS);

        Cache::put($attemptsKey, 0, self::TTL_SECONDS);

        RateLimiter::hit($rateLimitKey, self::RATE_LIMIT_DECAY);

        // Send via AiSensy if not using test OTP
        if (! $usesTestOtp) {
            $sent = $this->aiSensyService->sendOtp($cleanPhone, $otp);
            if (! $sent) {
                Log::warning('COD OTP delivery via AiSensy failed, check credentials.', ['phone' => substr($cleanPhone, -4)]);
                // If SMS fails in production, don't silently block the user if gateway is down
                if (app()->environment('production')) {
                    throw ValidationException::withMessages([
                        'cod_otp' => ['Unable to send verification code at the moment. Please try again or choose an online payment method.'],
                    ]);
                }
            }
        }

        $maskedPhone = substr($cleanPhone, 0, 2) . '******' . substr($cleanPhone, -4);

        $payload = [
            'success' => true,
            'message' => 'Verification code sent successfully to ' . $maskedPhone . '.' . ($usesTestOtp ? ' Use 123456.' : ''),
            'phone_masked' => $maskedPhone,
            'resend_after' => 30,
        ];

        if ($isDevelopment && app()->environment('local')) {
            $payload['otp'] = $otp;
        }

        return $payload;
    }

    /**
     * Verify the supplied OTP for the user and address.
     *
     * @throws ValidationException
     */
    public function verifyOtp(User $user, ?string $otp, int $addressId): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        if (empty($otp)) {
            throw ValidationException::withMessages([
                'cod_otp' => ['Please enter the 6-digit verification code sent to your phone.'],
            ]);
        }

        $cacheKey = self::CACHE_PREFIX . $user->id;
        $attemptsKey = self::ATTEMPTS_PREFIX . $user->id;

        $cached = Cache::get($cacheKey);
        if (! $cached || ! is_array($cached)) {
            throw ValidationException::withMessages([
                'cod_otp' => ['Verification code has expired or has not been requested. Please request a new OTP.'],
            ]);
        }

        $attempts = (int) Cache::get($attemptsKey, 0);
        if ($attempts >= self::MAX_ATTEMPTS) {
            Cache::forget($cacheKey);
            Cache::forget($attemptsKey);
            throw ValidationException::withMessages([
                'cod_otp' => ['Too many incorrect verification attempts. Please request a new OTP.'],
            ]);
        }

        // Verify address consistency
        if ((int) ($cached['address_id'] ?? 0) !== $addressId) {
            throw ValidationException::withMessages([
                'cod_otp' => ['Delivery address changed during checkout. Please verify OTP for the selected address.'],
            ]);
        }

        // Check test OTP in non-production environments
        $isTestOtp = in_array($otp, ['123456'], true) && (
            app()->environment('local', 'testing') || (bool) config('services.aisensy.use_test_otp', false)
        );

        if (! $isTestOtp && ! Hash::check($otp, $cached['hash'] ?? '')) {
            Cache::increment($attemptsKey);
            $remaining = self::MAX_ATTEMPTS - ($attempts + 1);
            $suffix = $remaining > 0 ? " ({$remaining} attempts remaining)" : '';

            throw ValidationException::withMessages([
                'cod_otp' => ['Invalid verification code' . $suffix . '. Please check and try again.'],
            ]);
        }

        // OTP verified successfully; clear cache
        Cache::forget($cacheKey);
        Cache::forget($attemptsKey);

        return true;
    }
}

