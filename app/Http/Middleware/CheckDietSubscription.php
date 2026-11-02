<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckDietSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Expecting a child_id in route parameters or query/session
        // E.g., /user/assessment/{child_id}
        $childId = $request->route('child') ?? $request->query('child_id');

        if (!$childId) {
            // For now, if there is no explicit child passed, just check if user has ANY active subscription
            $hasActive = $request->user()->dietSubscriptions()->where('status', 'active')->exists();
            if (!$hasActive) {
                return redirect()->route('user.subscription')->with('error', 'You do not have an active diet service subscription.');
            }
            return $next($request);
        }

        // Specific child check
        $child = $request->user()->children()->find($childId);
        if (!$child) {
            abort(403, 'Unauthorized access to child profile.');
        }

        $hasActive = $child->dietSubscriptions()->where('status', 'active')->exists();

        if (!$hasActive) {
            return redirect()->route('user.subscription')->with('error', 'The subscription for this child has expired or is inactive. Please renew to continue.');
        }

        return $next($request);
    }
}
