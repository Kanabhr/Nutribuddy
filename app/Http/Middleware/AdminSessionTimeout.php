<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces an idle timeout for the admin guard only.
 * Timeout is configured via ADMIN_SESSION_TIMEOUT (minutes, default 120).
 * On expiry the session is invalidated and the user is redirected to the
 * sign-in page with a clear message.
 */
class AdminSessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('admin')->check()) {
            return $next($request);
        }

        $timeoutMinutes = (int) config('admin.session_timeout', 120);
        $key = 'admin_last_activity';
        $lastActivity = $request->session()->get($key);

        if ($lastActivity !== null && (time() - $lastActivity) > ($timeoutMinutes * 60)) {
            Auth::guard('admin')->logout();
            // Clear 2FA keys explicitly — invalidate() flushes all session data
            // but we forget them first to be explicit about intent.
            $request->session()->forget(['admin_2fa_verified', 'admin_2fa_last_ts', '2fa_pending_secret']);
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('signin')
                ->with('error', 'Your session expired due to inactivity. Please sign in again.');
        }

        $request->session()->put($key, time());

        return $next($request);
    }
}
