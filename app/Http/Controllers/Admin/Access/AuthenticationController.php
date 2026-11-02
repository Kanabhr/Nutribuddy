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
                $request->session()->regenerate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => $admin->is_active
                        ? 'Unauthorized access. Only admins can login here.'
                        : 'This admin account has been deactivated.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            return redirect()->intended(route('admin.index'))
                ->with('success', 'Logged in successfully.');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('signin')
            ->with('success', 'Logged out successfully.');
    }

    public function signUp()
    {
        return view('frontend.auth.signup');
    }
}
