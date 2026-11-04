<?php

namespace App\Http\Middleware;

use App\Support\PlanAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckDietSubscription
{
    /**
     * Requires a usable (active, paid/free, not expired) diet subscription.
     * Optional feature check: ->middleware('<alias>:health_score') also requires a plan that includes it.
     */
    public function handle(Request $request, Closure $next, ?string $feature = null): Response
    {
        $user = $request->user();
        $childId = $request->route('child') ?? $request->query('child_id');

        if (! $childId) {
            $slug = PlanAccess::slugForUser($user);
            $message = 'You do not have an active diet service subscription.';
        } else {
            $child = $user->children()->find($childId);
            if (! $child) {
                abort(403, 'Unauthorized access to child profile.');
            }
            $slug = PlanAccess::slugForChild($child);
            $message = 'The subscription for this child has expired or is inactive. Please renew to continue.';
        }

        if (! $slug) {
            return redirect()->route('user.subscription')->with('error', $message);
        }

        if ($feature && ! PlanAccess::allows($slug, $feature)) {
            return redirect()->route('diet_chart')->with('error', 'Upgrade your plan to use this feature.');
        }

        return $next($request);
    }
}
