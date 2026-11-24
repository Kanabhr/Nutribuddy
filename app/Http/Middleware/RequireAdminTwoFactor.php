<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * After the admin guard authenticates, any admin who has 2FA enabled must
 * complete the TOTP challenge before accessing any admin route.
 *
 * The challenge completion is recorded in the session key 'admin_2fa_verified',
 * bound to the admin's id. A flag set for a different admin must NOT pass.
 * The 2FA challenge routes themselves are excluded so the user can submit the form.
 */
class RequireAdminTwoFactor
{
    // Routes that must remain accessible during the 2FA challenge.
    private const ALLOW_LIST = [
        'admin.2fa.challenge',
        'admin.2fa.verify',
        'admin.logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Only admin pages are subject to this check; leave the public shop alone.
        if (! $request->is('admin', 'admin/*')) {
            return $next($request);
        }
        if (! auth('admin')->check()) {
            return $next($request);
        }

        $admin = auth('admin')->user();

        if (! $admin->two_factor_confirmed_at) {
            // 2FA not enabled for this admin — pass through.
            return $next($request);
        }

        // Strict comparison: the flag must be exactly this admin's id.
        if ($request->session()->get('admin_2fa_verified') === $admin->id) {
            return $next($request);
        }

        // Allow the challenge page and its POST route.
        $routeName = $request->route()?->getName();
        if ($routeName && in_array($routeName, self::ALLOW_LIST, true)) {
            return $next($request);
        }

        return redirect()->route('admin.2fa.challenge');
    }
}
