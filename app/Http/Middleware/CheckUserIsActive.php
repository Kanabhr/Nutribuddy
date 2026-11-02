<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        foreach (['admin' => 'signin', 'web' => 'home'] as $guard => $loginRoute) {
            $user = auth()->guard($guard)->user();
            if (! $user || $user->is_active) {
                continue;
            }

            auth()->guard($guard)->logout();
            $request->session()->regenerate();
            $request->session()->regenerateToken();

            $routeParameters = $guard === 'web' ? ['login' => 1] : [];

            return redirect()->route($loginRoute, $routeParameters)
                ->with('error', 'Your account has been deactivated. Please contact support.');
        }

        return $next($request);
    }
}
