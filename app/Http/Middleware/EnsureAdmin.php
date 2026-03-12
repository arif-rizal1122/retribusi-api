<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        // Allow all internal staff roles (App\Models\User) 
        // OR citizens (App\Models\Taxpayer) for shared endpoints
        // Note: Specific route groups in api.php should handle further role-based restrictions
        if (!auth()->check() || (!($user instanceof \App\Models\User) && !($user instanceof \App\Models\Taxpayer))) {
            return response()->json([
                'message' => 'Forbidden: Internal access required.'
            ], 403);
        }

        return $next($request);
    }
}
