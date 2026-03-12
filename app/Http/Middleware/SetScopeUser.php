<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Scopes\RetributionTypeScope;

class SetScopeUser
{
    /**
     * After authentication is complete, inject the resolved user into 
     * RetributionTypeScope so it can apply role-based filtering.
     * 
     * This middleware MUST run AFTER auth:sanctum to avoid infinite recursion.
     */
    public function handle(Request $request, Closure $next)
    {
        // At this point, Sanctum has already resolved the user.
        // It's safe to call $request->user() because auth middleware already ran.
        $user = $request->user();
        
        if ($user) {
            RetributionTypeScope::setAuthenticatedUser($user);
        }

        $response = $next($request);

        // Reset for next request (important for long-running processes)
        RetributionTypeScope::resetUser();

        return $response;
    }
}
