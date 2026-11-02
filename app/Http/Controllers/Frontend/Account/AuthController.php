<?php

namespace App\Http\Controllers\Frontend\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\Account\SendOtpRequest;
use App\Http\Requests\Frontend\Account\VerifyOtpRequest;
use App\Models\User;
use App\Services\Notifications\AiSensyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return redirect()->route('home', ['login' => 1]);
    }

    public function sendOtp(SendOtpRequest $request, AiSensyService $aiSensy)
    {
        $phone = $request->phone;
        $isDevelopment = app()->environment(['local', 'testing']);
        $isUatOtpEnabled = app()->environment('uat') && (bool) config('services.aisensy.uat_otp_enabled');
        $usesTestOtp = $isDevelopment || $isUatOtpEnabled;
        $otp = $usesTestOtp ? '123456' : (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(10);

        $user = User::where('phone', $phone)->first();
        if ($user && $user->role !== 'customer') {
            return response()->json([
                'success' => false,
                'message' => 'Admin accounts cannot login via OTP. Please use the admin portal.',
            ], 403);
        }

        if (! $user) {
            $user = User::create([
                'name' => 'Customer '.substr($phone, -4),
                'phone' => $phone,
                'email' => $phone.'@nutribuddy.com',
                'password' => Hash::make(Str::random(16)),
                'role' => 'customer',
            ]);
        }

        $user->otp = Hash::make($otp);
        $user->otp_expires_at = $expiresAt;
        $user->save();

        if (! $usesTestOtp && ! $aiSensy->sendOtp($phone, $otp)) {
            $user->forceFill(['otp' => null, 'otp_expires_at' => null])->save();

            return response()->json([
                'success' => false,
                'message' => 'Unable to send a verification code right now. Please try again later.',
            ], 503);
        }

        $payload = [
            'success' => true,
            'message' => 'OTP sent successfully.'.($usesTestOtp ? ' Use 123456.' : ''),
        ];

        if ($isDevelopment && app()->environment('local')) {
            $payload['otp'] = $otp;
        }

        return response()->json($payload);
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        $user = User::where('phone', $request->phone)
            ->where('otp_expires_at', '>', now())
            ->first();

        if ($user && $user->otp && Hash::check($request->otp, $user->otp)) {
            if ($user->role !== 'customer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin accounts cannot login via OTP.',
                ], 403);
            }

            if (! $user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact support.',
                ], 403);
            }

            $user->otp = null;
            $user->otp_expires_at = null;
            $user->save();

            Auth::guard('web')->login($user, true);
            $request->session()->regenerate();

            $redirect = $this->safeRedirect($request->redirect_to);

            return response()->json([
                'success' => true,
                'message' => 'Logged in successfully.',
                'redirect' => $redirect,
                'csrf_token' => csrf_token(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid or expired OTP.',
        ], 422);
    }

    public function logout(Request $request)
    {
        // Only log out the frontend 'web' guard.
        // Do NOT call $request->session()->invalidate() here because that
        // destroys the ENTIRE session (including the admin guard session),
        // which would log the admin out of the backend panel too.
        Auth::guard('web')->logout();
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Logged out successfully.');
    }

    private function safeRedirect(?string $redirectTo): string
    {
        if (! $redirectTo) {
            return route('userdashboard');
        }

        if (str_starts_with($redirectTo, '/') && ! str_starts_with($redirectTo, '//')) {
            return $redirectTo;
        }

        if (URL::isValidUrl($redirectTo) && parse_url($redirectTo, PHP_URL_HOST) === request()->getHost()) {
            return $redirectTo;
        }

        return route('userdashboard');
    }
}
